<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();      // pemilik ujian
            $t->foreignId('exam_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('ai_api_key_id')->nullable()->constrained('ai_api_keys')->nullOnDelete();
            $t->string('kind', 16)->default('essay');                                  // essay | short
            $t->string('model', 80)->nullable();
            $t->boolean('used_shared_key')->default(false);
            $t->unsignedInteger('prompt_tokens')->default(0);
            $t->unsignedInteger('completion_tokens')->default(0);
            $t->unsignedInteger('total_tokens')->default(0);
            $t->unsignedSmallInteger('answers_count')->default(0);
            $t->boolean('success')->default(true);
            $t->string('error_code', 40)->nullable();
            $t->unsignedInteger('latency_ms')->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->index('created_at');
            $t->index(['user_id', 'created_at']);
            $t->index(['success', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};