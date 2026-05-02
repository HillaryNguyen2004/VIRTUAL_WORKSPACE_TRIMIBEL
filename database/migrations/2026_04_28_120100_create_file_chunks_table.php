<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('file_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('upload_session_id')->index();
            $table->unsignedInteger('chunk_number');
            $table->unsignedBigInteger('chunk_size'); // size of this chunk
            $table->string('chunk_hash', 64)->nullable(); // SHA256 hash for validation
            $table->string('stored_path', 255); // temporary storage path
            $table->enum('status', ['pending', 'uploaded', 'verified', 'assembled', 'failed'])->default('pending');
            $table->timestamps();
            
            $table->foreign('upload_session_id')->references('id')->on('upload_sessions')->onDelete('cascade');
            $table->unique(['upload_session_id', 'chunk_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_chunks');
    }
};
