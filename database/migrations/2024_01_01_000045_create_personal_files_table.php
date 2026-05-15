<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('stored_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default('0');
            $table->enum('ingest_status', ['pending','processing','completed','failed'])->default('pending');
            $table->text('ingest_error')->nullable();
            $table->unsignedInteger('chunk_count')->default('0');
            $table->timestamp('ingested_at')->nullable();
            $table->longText('searchable_text')->nullable();
            $table->timestamps();
            $table->index('folder_id', 'personal_files_folder_id_foreign');
            $table->index(['user_id', 'folder_id'], 'personal_files_user_id_folder_id_index');
            $table->index('document_id', 'personal_files_document_id_foreign');
            $table->index(['user_id', 'document_id'], 'personal_files_user_id_document_id_index');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('set null');
            $table->foreign('folder_id')->references('id')->on('personal_folders')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_files');
    }
};
