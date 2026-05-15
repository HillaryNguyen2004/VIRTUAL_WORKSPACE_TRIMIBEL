<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_private')->default('0');
            $table->boolean('allow_messages')->default('1');
            $table->boolean('admin_only')->default('0');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->timestamps();
            $table->index('created_by', 'channels_created_by_index');
            $table->index('conversation_id', 'channels_conversation_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
