<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', fn (Blueprint $t) => $t->string('grading_mode', 12)->nullable());

        // Paragraph: old graded essay -> scoring, old ignored -> non_scoring
        DB::table('questions')->where('field_type', 'essay')->where('gform_item_type', 'paragraph')->update(['grading_mode' => 'scoring']);
        DB::table('questions')->where('field_type', 'ignored')->where('gform_item_type', 'paragraph')->update(['grading_mode' => 'non_scoring']);

        // field_type = Role: identity | stimulus | main
        DB::statement("ALTER TABLE questions MODIFY field_type VARCHAR(20) NOT NULL DEFAULT 'main'");
        DB::table('questions')->whereIn('field_type', ['essay', 'ignored'])->update(['field_type' => 'main']);
        DB::table('questions')->where('gform_item_type', 'file_upload')->update(['field_type' => 'stimulus']);
        DB::table('questions')->whereIn('gform_item_type', ['scale', 'rating'])->update(['gform_item_type' => 'linear_scale']);
        DB::table('questions')->where('gform_item_type', 'grid_row')->update(['gform_item_type' => 'multiple_choice_grid']);

        Schema::table('submissions', function (Blueprint $t) {
            $t->json('identity')->nullable();               // {question_id: value}
            $t->string('identity_key', 191)->nullable();    // duplicate detection
            $t->index(['exam_id', 'identity_key']);
        });

        // Backfill identity for existing submissions from raw_payload
        $cache = [];
        DB::table('submissions')->orderBy('id')->chunkById(200, function ($rows) use (&$cache) {
            foreach ($rows as $s) {
                $cache[$s->exam_id] ??= DB::table('questions')->where('exam_id', $s->exam_id)
                    ->where('field_type', 'identity')->pluck('id', 'gform_item_id')->all();
                $payload  = json_decode((string) $s->raw_payload, true);
                $identity = [];
                foreach ($cache[$s->exam_id] as $itemId => $qid) {
                    $v = trim(implode(', ', array_column($payload['answers'][$itemId]['textAnswers']['answers'] ?? [], 'value')));
                    if ($v !== '') { $identity[$qid] = $v; }
                }
                DB::table('submissions')->where('id', $s->id)->update([
                    'identity'     => $identity ? json_encode($identity, JSON_UNESCAPED_UNICODE) : null,
                    'identity_key' => $identity ? mb_substr(mb_strtolower(implode('|', $identity)), 0, 191) : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $t) {
            $t->dropIndex(['exam_id', 'identity_key']);
            $t->dropColumn(['identity', 'identity_key']);
        });
        Schema::table('questions', fn (Blueprint $t) => $t->dropColumn('grading_mode'));
        // field_type values are not restored
    }
};