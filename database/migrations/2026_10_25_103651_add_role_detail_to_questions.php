<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $t) {
            $t->string('role_detail', 60)->nullable();
            $t->string('role_reason', 300)->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('questions', fn (Blueprint $t) => $t->dropColumn(['role_detail', 'role_reason']));
    }
};
