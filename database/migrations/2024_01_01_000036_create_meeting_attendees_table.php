<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_attendees', function (Blueprint $table) {
            $table->id();
            $table->string('meeting_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('speaker_tag')->nullable();
            $table->string('name');
            $table->string('avatar_url')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['meeting_id', 'user_id']);
            $table->index('meeting_id', 'meeting_attendees_meeting_id_index');
            $table->index('user_id', 'meeting_attendees_user_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_attendees');
    }
};
