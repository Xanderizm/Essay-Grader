<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // false = akun dibuat lewat Google dan belum punya password sendiri
            $table->boolean('password_set')->default(true);
            // bukti persetujuan Ketentuan Layanan dan Kebijakan Privasi
            $table->timestamp('terms_accepted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_set', 'terms_accepted_at']);
        });
    }
};
