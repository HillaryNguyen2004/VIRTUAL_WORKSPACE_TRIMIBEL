<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('room_name');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('attendees_count')->default('0');
            $table->integer('duration_seconds')->default('0');
            $table->timestamps();
            $table->unique('room_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
