<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $t) {
            if (! Schema::hasColumn('exams', 'gform_deleted_at')) { $t->timestamp('gform_deleted_at')->nullable(); }
            if (! Schema::hasColumn('exams', 'gform_dismissed_at')) { $t->timestamp('gform_dismissed_at')->nullable(); }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $t) {
            foreach (['gform_deleted_at', 'gform_dismissed_at'] as $c) {
                if (Schema::hasColumn('exams', $c)) { $t->dropColumn($c); }
            }
        });
    }
};