<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('category', 50)->default('personal');
            $table->text('description')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('recurrence_type', 20)->nullable()->default('none');
            $table->integer('recurrence_interval')->nullable()->default('1');
            $table->date('recurrence_end_date')->nullable();
            $table->integer('recurrence_count')->nullable();
            $table->timestamps();
            $table->index('user_id', 'calendar_events_user_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
