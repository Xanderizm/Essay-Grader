<?php

namespace Tests\Feature;

use App\Jobs\ProcessEssayGrading;
use App\Models\Exam;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function makeExam(User $owner): Exam
    {
        return Exam::create([
            'user_id'         => $owner->id,
            'gform_id'        => 'FORM_' . Str::random(6),
            'title'           => 'Ujian Uji',
            'analysis_status' => 'success',
            'status'          => 'active',
            'webhook_token'   => Str::random(40),
        ]);
    }

    private function makeQuestion(Exam $exam): Question
    {
        return Question::create([
            'exam_id'         => $exam->id,
            'gform_item_id'   => Str::random(8),
            'question_text'   => 'Soal uji',
            'answer_key'      => 'Kunci',
            'field_type'      => 'main',
            'grading_mode'    => 'scoring',
            'gform_item_type' => 'paragraph',
            'order'           => 1,
        ]);
    }

    private function makeAnswer(Question $q, array $attrs = []): StudentAnswer
    {
        $s = Submission::create([
            'exam_id'           => $q->exam_id,
            'gform_response_id' => Str::random(10),
            'nim'               => (string) random_int(1000, 9999),
            'name'              => 'Mahasiswa',
        ]);

        return StudentAnswer::create(array_merge([
            'submission_id'     => $s->id,
            'question_id'       => $q->id,
            'raw_answer'        => 'Jawaban',
            'score_content'     => 60,
            'score_logic'       => 60,
            'score_structure'   => 60,
            'final_score'       => 60,
            'ai_category'       => 'Cukup',
            'ai_analysis'       => 'ok',
            'processing_status' => 'success',
        ], $attrs));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_owner_can_open_results_page(): void
    {
        $owner = User::factory()->create();
        $exam  = $this->makeExam($owner);

        $this->actingAs($owner)->get(route('exams.show', $exam))->assertOk();
    }

    public function test_other_lecturer_is_forbidden_everywhere(): void
    {
        $owner    = User::factory()->create();
        $other    = User::factory()->create();
        $exam     = $this->makeExam($owner);
        $question = $this->makeQuestion($exam);
        $answer   = $this->makeAnswer($question);
        $scores   = ['score_content' => 90, 'score_logic' => 90, 'score_structure' => 90];

        $this->actingAs($other);

        $this->get(route('exams.show', $exam))->assertForbidden();
        $this->get(route('exams.edit', $exam))->assertForbidden();
        $this->get(route('exams.export', $exam))->assertForbidden();
        $this->post(route('exams.activate', $exam))->assertForbidden();
        $this->post(route('exams.sync', $exam))->assertForbidden();
        $this->delete(route('exams.destroy', $exam))->assertForbidden();
        $this->put(route('questions.update', $question), ['answer_key' => 'x', 'grading_mode' => 'scoring'])->assertForbidden();
        $this->post(route('questions.regrade', $question))->assertForbidden();
        $this->post(route('answers.override', $answer), $scores)->assertForbidden();
        $this->post(route('answers.revert', $answer))->assertForbidden();

        $this->assertDatabaseHas('exams', ['id' => $exam->id]);
        $this->assertEquals(60, $answer->fresh()->final_score);
    }

    public function test_api_key_and_status_pages_are_admin_only(): void
    {
        $this->actingAs(User::factory()->create())->get(route('keys.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.status'))->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('keys.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.status'))->assertOk();
    }

    public function test_override_recalculates_score_and_can_be_reverted(): void
    {
        $owner  = User::factory()->create();
        $answer = $this->makeAnswer($this->makeQuestion($this->makeExam($owner)));

        $this->actingAs($owner)->post(route('answers.override', $answer), [
            'score_content'   => 80,
            'score_logic'     => 75,
            'score_structure' => 70,
            'override_note'   => 'Sudah mencakup poin utama',
        ])->assertRedirect();

        $answer->refresh();
        $this->assertTrue($answer->is_overridden);
        $this->assertEquals(75.0, $answer->final_score);
        $this->assertSame('Baik', $answer->ai_category);
        $this->assertEquals(60, $answer->ai_original['score_content']);
        $this->assertSame('Sudah mencakup poin utama', $answer->override_note);

        // Nilai di luar 0-100 ditolak
        $this->actingAs($owner)->post(route('answers.override', $answer), [
            'score_content' => 150, 'score_logic' => 75, 'score_structure' => 70,
        ])->assertSessionHasErrors('score_content');

        // Kembalikan ke nilai AI
        $this->actingAs($owner)->post(route('answers.revert', $answer))->assertRedirect();

        $answer->refresh();
        $this->assertFalse($answer->is_overridden);
        $this->assertEquals(60.0, $answer->final_score);
        $this->assertSame('Cukup', $answer->ai_category);
    }

    public function test_regrade_skips_overridden_answers(): void
    {
        Queue::fake();

        $owner    = User::factory()->create();
        $question = $this->makeQuestion($this->makeExam($owner));
        $kept     = $this->makeAnswer($question, ['is_overridden' => true, 'override_note' => 'koreksi']);
        $regraded = $this->makeAnswer($question);

        $this->actingAs($owner)->post(route('questions.regrade', $question))->assertRedirect();

        $this->assertSame('success', $kept->fresh()->processing_status);
        $this->assertTrue($kept->fresh()->is_overridden);

        $this->assertSame('pending', $regraded->fresh()->processing_status);
        $this->assertNull($regraded->fresh()->final_score);

        Queue::assertPushed(ProcessEssayGrading::class);
    }

    public function test_deleting_exam_removes_all_related_data(): void
    {
        $owner = User::factory()->create();
        $exam  = $this->makeExam($owner);
        $this->makeAnswer($this->makeQuestion($exam));

        $this->actingAs($owner)->delete(route('exams.destroy', $exam))->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('submissions', 0);
        $this->assertDatabaseCount('student_answers', 0);
    }

    public function test_save_detects_changes_and_activation_requires_scored_paragraph(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $exam  = $this->makeExam($owner);
        $exam->update(['status' => 'draft']);
        $q = $this->makeQuestion($exam);
        $this->actingAs($owner);

        $this->putJson(route('exams.fields', $exam), ['roles' => [$q->id => 'main'], 'modes' => [$q->id => 'scoring'], 'keys' => [$q->id => 'Kunci']])
            ->assertOk()->assertJson(['ok' => true, 'changed' => false, 'message' => 'Tidak ada perubahan.']);

        $this->putJson(route('exams.fields', $exam), ['modes' => [$q->id => 'non_scoring']])
            ->assertOk()->assertJson(['changed' => true, 'message' => 'Perubahan disimpan.']);
        $this->assertSame('non_scoring', $q->fresh()->grading_mode);

        $this->putJson(route('exams.fields', $exam), ['modes' => [$q->id => 'non_scoring'], 'activate' => 1])
            ->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertSame('draft', $exam->fresh()->status);

        $this->putJson(route('exams.fields', $exam), ['modes' => [$q->id => 'scoring'], 'activate' => 1])
            ->assertOk()->assertJson(['activated' => true]);
        $this->assertSame('active', $exam->fresh()->status);
    }

    public function test_live_endpoint_is_owner_only_and_supports_etag(): void
    {
        $owner = User::factory()->create();
        $exam  = $this->makeExam($owner);
        $exam->update(['status' => 'draft']);

        $this->actingAs(User::factory()->create())->getJson(route('exams.live', $exam))->assertForbidden();

        $res = $this->actingAs($owner)->getJson(route('exams.live', $exam))->assertOk()->assertJsonStructure(['version', 'submissions', 'analysis']);
        $this->getJson(route('exams.live', $exam), ['If-None-Match' => $res->headers->get('ETag')])->assertStatus(304);
    }
}