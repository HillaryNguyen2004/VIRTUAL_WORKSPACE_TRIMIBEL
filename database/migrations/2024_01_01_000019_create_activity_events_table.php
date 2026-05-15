<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('role', 50)->nullable();
            $table->string('event_type', 50);
            $table->string('module', 50);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->index('occurred_at', 'idx_company_time');
            $table->index(['user_id', 'occurred_at'], 'idx_user_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_events');
    }
};
