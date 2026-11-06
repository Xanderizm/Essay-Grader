<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus tabel lama: tabel "anak" dulu, tabel "induk" belakangan
        Schema::dropIfExists('student_answers');
        Schema::dropIfExists('students');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('exams');

        // 1. Ujian (satu Google Form milik satu dosen)
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();               // diisi dari judul Form setelah analisis
            $table->text('gform_url')->nullable();             // link yang ditempel dosen
            $table->string('gform_id');                        // ID Form hasil ekstraksi dari link

            $table->enum('analysis_status', ['pending', 'analyzing', 'success', 'failed'])->default('pending');
            $table->text('analysis_error')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->json('form_structure')->nullable();        // struktur mentah Form (riwayat)

            $table->enum('status', ['draft', 'active', 'closed'])->default('draft'); // active = siap menerima jawaban
            $table->string('webhook_token', 64)->unique();
            $table->timestamps();

            $table->unique(['user_id', 'gform_id']);           // satu dosen tidak mendaftarkan Form yang sama dua kali
            $table->index(['user_id', 'status']);
        });

        // 2. Kolom/pertanyaan di Form (identitas, esai, atau diabaikan)
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('gform_item_id', 100)->nullable();  // ID item dari Google, dipakai untuk mencocokkan jawaban
            $table->string('gform_item_type', 50)->nullable(); // mis. paragraph, short_answer, multiple_choice
            $table->text('question_text');
            $table->text('description')->nullable();           // teks bantuan di bawah judul pertanyaan

            $table->enum('field_type', ['identity', 'essay', 'ignored'])->default('ignored');
            $table->enum('identity_role', ['nim', 'name', 'class', 'email'])->nullable();

            $table->text('answer_key')->nullable();            // kunci jawaban/rubrik, diisi dosen untuk soal esai
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['exam_id', 'gform_item_id']);
            $table->index(['exam_id', 'field_type']);
        });

        // 3. Satu pengiriman Form oleh satu mahasiswa
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('gform_response_id', 100)->nullable(); // mencegah data ganda jika kiriman terulang
            $table->string('nim', 30)->nullable();
            $table->string('name')->nullable();
            $table->string('class', 50)->nullable();
            $table->string('email')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->json('raw_payload')->nullable();             // data asli dari Google
            $table->timestamps();

            $table->unique(['exam_id', 'gform_response_id']);
            $table->index(['exam_id', 'nim']);
        });

        // 4. Jawaban esai per soal beserta hasil penilaian AI
        Schema::create('student_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->longText('raw_answer')->nullable();

            // Skor per parameter (0-100)
            $table->unsignedTinyInteger('score_content')->nullable();    // Pemahaman Konten
            $table->unsignedTinyInteger('score_logic')->nullable();      // Logika Argumen
            $table->unsignedTinyInteger('score_structure')->nullable();  // Struktur Tulisan
            $table->decimal('final_score', 5, 2)->nullable();            // rata-rata ketiganya
            $table->string('ai_category', 20)->nullable();               // Sangat Baik / Baik / Cukup / Kurang
            $table->text('ai_analysis')->nullable();

            $table->enum('processing_status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'question_id']);
            $table->index(['processing_status', 'question_id']);
        });
    }

    public function down(): void
    {
        // Struktur lama tidak dipulihkan; gunakan cadangan dari Workbench bila perlu
        Schema::dropIfExists('student_answers');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('exams');
    }
};