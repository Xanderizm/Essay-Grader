<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_email')->nullable();
            $table->text('google_token')->nullable();            // disimpan terenkripsi
            $table->text('google_refresh_token')->nullable();    // disimpan terenkripsi
            $table->timestamp('google_token_expires_at')->nullable();
            $table->timestamp('google_connected_at')->nullable();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->timestamp('last_synced_at')->nullable();     // dipakai di langkah penarikan jawaban
            $table->text('sync_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_email', 'google_token', 'google_refresh_token',
                'google_token_expires_at', 'google_connected_at',
            ]);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['last_synced_at', 'sync_error']);
        });
    }
};