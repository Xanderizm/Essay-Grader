<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->longText('raw_answer');
            $table->unsignedTinyInteger('ai_score')->nullable();
            $table->string('ai_relevance_status', 30)->nullable();
            $table->text('ai_analysis')->nullable();
            $table->enum('processing_status', ['pending', 'processing', 'success', 'failed'])
                  ->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'question_id']);
            $table->index(['processing_status', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_answers');
    }
};