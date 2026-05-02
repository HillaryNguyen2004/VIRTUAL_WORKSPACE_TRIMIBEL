<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('upload_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('session_id', 64)->unique(); // unique session identifier
            $table->string('original_filename', 255);
            $table->unsignedBigInteger('total_size'); // total file size in bytes
            $table->unsignedInteger('total_chunks');
            $table->unsignedInteger('uploaded_chunks')->default(0);
            $table->enum('status', ['pending', 'uploading', 'completed', 'failed', 'expired'])->default('pending');
                $table->string('error_message')->nullable();
                $table->string('assembled_path')->nullable(); // final file path after assembly
            $table->unsignedBigInteger('folder_id')->nullable()->index();
            $table->timestamp('expires_at')->nullable(); // session expiry for cleanup
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('upload_sessions');
    }
};
