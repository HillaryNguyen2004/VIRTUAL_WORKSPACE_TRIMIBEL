<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_workspaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug');
            $table->enum('visibility', ['private','team','public'])->default('private');
            $table->boolean('allow_others_upload')->default('0');
            $table->string('folder_path');
            $table->integer('file_count')->default('0');
            $table->bigInteger('storage_size')->default('0');
            $table->string('status')->default('active');
            $table->timestamp('last_ingested_at')->nullable();
            $table->timestamps();
            $table->unique('slug');
            $table->index('user_id', 'ai_workspaces_user_id_index');
            $table->index('visibility', 'ai_workspaces_visibility_index');
            $table->index('status', 'ai_workspaces_status_index');
            $table->index('created_at', 'ai_workspaces_created_at_index');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_workspaces');
    }
};
