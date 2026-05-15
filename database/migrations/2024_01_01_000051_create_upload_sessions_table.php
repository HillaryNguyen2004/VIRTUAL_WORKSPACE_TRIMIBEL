<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('session_id', 64);
            $table->string('original_filename');
            $table->unsignedBigInteger('total_size');
            $table->unsignedInteger('total_chunks');
            $table->unsignedInteger('uploaded_chunks')->default('0');
            $table->enum('status', ['pending','uploading','completed','failed','expired'])->default('pending');
            $table->string('error_message')->nullable();
            $table->string('assembled_path')->nullable();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique('session_id');
            $table->index('user_id', 'upload_sessions_user_id_index');
            $table->index('folder_id', 'upload_sessions_folder_id_index');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Deferred FK: file_chunks.upload_session_id -> upload_sessions.id
        Schema::table('file_chunks', function (Blueprint $table) {
            $table->foreign('upload_session_id')->references('id')->on('upload_sessions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_sessions');
    }
};
