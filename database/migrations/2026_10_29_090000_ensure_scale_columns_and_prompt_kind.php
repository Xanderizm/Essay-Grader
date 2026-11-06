<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Aman dijalankan ulang: hanya menambah kolom yang belum ada
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'daily_quota')) { $t->unsignedInteger('daily_quota')->nullable(); }
            if (! Schema::hasColumn('users', 'deletion_requested_at')) { $t->timestamp('deletion_requested_at')->nullable(); }
        });

        if (! Schema::hasColumn('ai_api_keys', 'user_id')) {
            Schema::table('ai_api_keys', function (Blueprint $t) {
                $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $t->index(['user_id', 'is_active']);
            });
        }

        if (! Schema::hasColumn('questions', 'prompt_kind')) {
            Schema::table('questions', fn (Blueprint $t) => $t->string('prompt_kind', 12)->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('questions', 'prompt_kind')) {
            Schema::table('questions', fn (Blueprint $t) => $t->dropColumn('prompt_kind'));
        }
    }
};
