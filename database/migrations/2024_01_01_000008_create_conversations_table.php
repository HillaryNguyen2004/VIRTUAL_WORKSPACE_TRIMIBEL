<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->enum('type', ['direct','group'])->default('direct');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->index('created_by', 'conversations_created_by_foreign');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Deferred FK: channels.conversation_id -> conversations.id
        Schema::table('channels', function (Blueprint $table) {
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
