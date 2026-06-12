<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('session_id');
            $table->dateTime('login_at');
            $table->dateTime('last_activity_at');
            $table->dateTime('logout_at');
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();
            $table->index('user_id', 'user_id');
            $table->index('session_id', 'session_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
