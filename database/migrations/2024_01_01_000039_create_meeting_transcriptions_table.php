<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_transcriptions', function (Blueprint $table) {
            $table->id();
            $table->string('meeting_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('text');
            $table->timestamps();
            $table->index('user_id', 'meeting_transcriptions_user_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_transcriptions');
    }
};
