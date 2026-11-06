<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->boolean('is_overridden')->default(false);
            $table->text('override_note')->nullable();
            $table->timestamp('overridden_at')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('ai_original')->nullable();   // nilai asli dari AI, untuk dikembalikan
        });
    }

    public function down(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('overridden_by');
            $table->dropColumn(['is_overridden', 'override_note', 'overridden_at', 'ai_original']);
        });
    }
};