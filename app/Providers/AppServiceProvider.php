<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(fn ($notifiable, $url) => (new MailMessage)
            ->subject('Aktifkan akun Penilai Esai Anda')
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Terima kasih sudah mendaftar. Klik tombol di bawah untuk mengaktifkan akun Anda.')
            ->action('Aktifkan akun', $url)
            ->line('Tautan ini berlaku 60 menit. Jika Anda tidak merasa mendaftar, abaikan saja email ini.')
            ->salutation('Salam, Tim Penilai Esai'));

        ResetPassword::toMailUsing(fn ($notifiable, $token) => (new MailMessage)
            ->subject('Atur ulang password Penilai Esai')
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun Anda.')
            ->action('Atur ulang password', url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false)))
            ->line('Tautan berlaku 60 menit. Jika bukan Anda yang meminta, abaikan email ini. Password Anda tidak berubah.')
            ->salutation('Salam, Tim Penilai Esai'));
    }
}
