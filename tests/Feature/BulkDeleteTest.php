<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function exam(User $u, string $formId): Exam
    {
        return Exam::create(['user_id' => $u->id, 'gform_id' => $formId, 'webhook_token' => Str::random(40)]);
    }

    public function test_bulk_delete_removes_only_own_exams(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $a = $this->exam($owner, 'FORM_A');
        $b = $this->exam($owner, 'FORM_B');
        $c = $this->exam($owner, 'FORM_C');
        $foreign = $this->exam($other, 'FORM_X');

        $this->actingAs($owner)
            ->delete(route('exams.bulk-destroy'), ['ids' => [$a->id, $b->id, $foreign->id]])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('exams', ['id' => $a->id]);
        $this->assertDatabaseMissing('exams', ['id' => $b->id]);
        $this->assertDatabaseHas('exams', ['id' => $c->id]);
        $this->assertDatabaseHas('exams', ['id' => $foreign->id]);
    }

    public function test_bulk_delete_requires_at_least_one_id(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->delete(route('exams.bulk-destroy'), ['ids' => []])
            ->assertSessionHasErrors('ids');
    }
}