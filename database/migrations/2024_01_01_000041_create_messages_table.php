<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->text('content');
            $table->enum('type', ['text','image','file'])->default('text');
            $table->longText('metadata')->nullable();
            $table->string('file_name')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('file_type')->nullable();
            $table->timestamps();
            $table->index('conversation_id', 'messages_conversation_id_foreign');
            $table->index('user_id', 'messages_user_id_foreign');
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Deferred FK: message_reads.message_id -> messages.id
        Schema::table('message_reads', function (Blueprint $table) {
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
