<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('provider_name', 50);
            $table->string('label')->nullable();
            $table->text('api_key');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('daily_usage_count')->default(0);
            $table->date('usage_date')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('cooldown_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_api_keys');
    }
};