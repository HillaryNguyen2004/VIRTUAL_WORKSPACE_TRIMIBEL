<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id');
            $table->string('participant_id');
            $table->string('name')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
            $table->index('meeting_id', 'meeting_participants_meeting_id_index');
            $table->index('participant_id', 'meeting_participants_participant_id_index');
            $table->index('joined_at', 'meeting_participants_joined_at_index');
            $table->index('left_at', 'meeting_participants_left_at_index');
            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');
    }
};
