<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('upload_session_id');
            $table->unsignedInteger('chunk_number');
            $table->unsignedBigInteger('chunk_size');
            $table->string('chunk_hash', 64)->nullable();
            $table->string('stored_path');
            $table->enum('status', ['pending','uploaded','verified','assembled','failed'])->default('pending');
            $table->timestamps();
            $table->unique(['upload_session_id', 'chunk_number']);
            $table->index('upload_session_id', 'file_chunks_upload_session_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_chunks');
    }
};
