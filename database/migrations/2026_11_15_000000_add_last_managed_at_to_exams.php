<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $t) {
            if (! Schema::hasColumn('exams', 'last_managed_at')) {
                $t->timestamp('last_managed_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $t) {
            if (Schema::hasColumn('exams', 'last_managed_at')) { $t->dropColumn('last_managed_at'); }
        });
    }
};