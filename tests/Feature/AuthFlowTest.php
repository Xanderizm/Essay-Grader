<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogle(array $profile): void
    {
        config(['google.client_id' => 'id', 'google.client_secret' => 'secret']);

        Http::preventStrayRequests();
        Http::fake([
            'oauth2.googleapis.com/token'    => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            'openidconnect.googleapis.com/*' => Http::response($profile),
        ]);
    }

    // Mensimulasikan klik tombol Google lalu kembalinya pengguna dari Google
    private function googleRoundTrip(string $intent, array $extra = [], ?string $stateOverride = null)
    {
        $this->post(route('auth.google'), ['intent' => $intent] + $extra)->assertRedirect();

        $state = $stateOverride ?? session('google_auth.state');

        return $this->get(route('auth.google.callback', ['code' => 'abc', 'state' => $state]));
    }

    public function test_register_needs_consent_then_goes_to_dashboard(): void
    {
        $payload = ['first_name' => 'Rani', 'last_name' => 'Putri', 'email' => 'rani@example.com', 'password' => 'kata-sandi-panjang'];

        $this->post(route('register'), $payload)->assertSessionHasErrors('agree');
        $this->assertDatabaseMissing('users', ['email' => 'rani@example.com']);

        $this->post(route('register'), $payload + ['agree' => '1'])->assertRedirect(route('verification.notice'));

        $this->assertAuthenticated();
        $user = User::where('email', 'rani@example.com')->firstOrFail();
        $this->assertSame('Rani Putri', $user->name);
        $this->assertNotNull($user->terms_accepted_at);
    }

    public function test_register_last_name_is_optional_and_short_password_is_rejected(): void
    {
        $this->post(route('register'), ['first_name' => 'Budi', 'email' => 'budi@example.com', 'password' => 'pendek', 'agree' => '1'])
            ->assertSessionHasErrors('password');

        $this->post(route('register'), ['first_name' => 'Budi', 'email' => 'budi@example.com', 'password' => 'kata-sandi-panjang', 'agree' => '1'])
            ->assertRedirect(route('verification.notice'));

        $this->assertSame('Budi', User::where('email', 'budi@example.com')->value('name'));
    }

    public function test_closed_mode_hides_registration_and_invite_mode_checks_code(): void
    {
        config(['registration.mode' => 'closed']);
        $this->get(route('register'))->assertNotFound();

        config(['registration.mode' => 'invite', 'registration.code' => 'kode-rahasia-panjang']);
        $payload = ['first_name' => 'Dina', 'email' => 'dina@example.com', 'password' => 'kata-sandi-panjang', 'agree' => '1'];

        $this->post(route('register'), $payload + ['invite_code' => 'salah'])->assertSessionHasErrors('invite_code');
        $this->assertDatabaseMissing('users', ['email' => 'dina@example.com']);

        $this->post(route('register'), $payload + ['invite_code' => 'kode-rahasia-panjang'])->assertRedirect(route('verification.notice'));
    }

    public function test_login_goes_to_dashboard_even_when_another_page_was_requested_first(): void
    {
        $user = User::factory()->create(['password' => Hash::make('kata-sandi-panjang')]);

        $this->get('/google')->assertRedirect('/login');

        $this->post('/login', ['email' => $user->email, 'password' => 'kata-sandi-panjang'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_logged_in_user_is_redirected_away_from_login_and_register(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect();
        $this->actingAs($user)->get('/register')->assertRedirect();
    }

    public function test_google_register_skips_consent_and_redirects_to_google(): void
    {
        config(['google.client_id' => 'id', 'google.client_secret' => 'secret']);
        $res = $this->post(route('auth.google'), ['intent' => 'register']);
        $res->assertSessionHasNoErrors();
        $this->assertStringStartsWith('https://accounts.google.com', $res->headers->get('Location'));
    }

    public function test_google_register_creates_account_and_goes_to_dashboard(): void
    {
        $this->fakeGoogle(['sub' => '1', 'email' => 'baru@example.com', 'email_verified' => true, 'name' => 'Baru Orang']);

        $this->googleRoundTrip('register', ['agree' => '1'])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $user = User::where('email', 'baru@example.com')->firstOrFail();
        $this->assertSame('Baru Orang', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertEquals(0, (int) $user->password_set);
    }

    public function test_google_login_creates_account_in_open_mode(): void
    {
        $this->fakeGoogle(['sub' => '1', 'email' => 'asing@example.com', 'email_verified' => true, 'name' => 'Orang Asing']);

        $this->googleRoundTrip('login')->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'asing@example.com']);
    }

    public function test_google_login_does_not_create_account_when_closed(): void
    {
        config(['registration.mode' => 'closed']);
        $this->fakeGoogle(['sub' => '2', 'email' => 'lain@example.com', 'email_verified' => true, 'name' => 'Lain']);

        $this->googleRoundTrip('login')->assertRedirect(route('register'))->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'lain@example.com']);
    }

    public function test_google_rejects_unverified_email_and_wrong_state(): void
    {
        $this->fakeGoogle(['sub' => '1', 'email' => 'x@example.com', 'email_verified' => false, 'name' => 'X']);

        $this->googleRoundTrip('register', ['agree' => '1'])->assertSessionHasErrors('google');
        $this->assertGuest();

        $this->googleRoundTrip('register', ['agree' => '1'], 'state-palsu')->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_google_login_takes_over_unverified_password_account_safely(): void
    {
        // Akun yang didaftarkan lewat password dengan email yang belum terbukti miliknya
        $account = User::factory()->unverified()->create([
            'email'    => 'korban@example.com',
            'password' => Hash::make('rahasia-pihak-lain'),
        ]);

        $this->fakeGoogle(['sub' => '9', 'email' => 'korban@example.com', 'email_verified' => true, 'name' => 'Pemilik Asli']);

        $this->googleRoundTrip('login')->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($account->fresh());
        $this->assertFalse(Hash::check('rahasia-pihak-lain', $account->fresh()->password));
        $this->assertEquals(0, (int) $account->fresh()->password_set);
        $this->assertNotNull($account->fresh()->email_verified_at);
    }

    public function test_google_login_keeps_password_of_verified_account(): void
    {
        $account = User::factory()->create([
            'email'    => 'sah@example.com',
            'password' => Hash::make('password-asli-panjang'),
        ]);

        $this->fakeGoogle(['sub' => '5', 'email' => 'sah@example.com', 'email_verified' => true, 'name' => 'Sah']);

        $this->googleRoundTrip('login')->assertRedirect(route('dashboard'));

        $this->assertTrue(Hash::check('password-asli-panjang', $account->fresh()->password));
    }

    public function test_unverified_account_must_activate_via_email_link(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'first_name' => 'Rani', 
            'email' => 'rani@example.com', 
            'password' => 'kata-sandi-panjang', 
            'agree' => '1'
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'rani@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);

        // PERBAIKAN: Ubah VerifyEmail::class bawaan Laravel menjadi class Notifikasi Custom milik Claude
        Notification::assertSentTo($user, \App\Notifications\QueuedVerifyEmail::class);

        $this->get('/dashboard')->assertRedirect(route('verification.notice'));   // belum aktif, ditahan

        auth()->logout();   // klik tautan dari perangkat lain
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->get($url)->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertAuthenticatedAs($user->fresh());
    }


    public function test_verification_link_with_wrong_hash_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $url  = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => 'salah']);

        $this->get($url)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }
}
