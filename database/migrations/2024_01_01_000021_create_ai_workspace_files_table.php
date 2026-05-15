<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_workspace_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('file_name');
            $table->string('original_name');
            $table->longText('file_path')->nullable();
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->integer('chunk_count')->default('0');
            $table->enum('ingest_status', ['pending','processing','completed','failed'])->default('pending');
            $table->text('ingest_error')->nullable();
            $table->timestamp('ingested_at')->nullable();
            $table->timestamps();
            $table->index('workspace_id', 'ai_workspace_files_workspace_id_index');
            $table->index('ingest_status', 'ai_workspace_files_ingest_status_index');
            $table->index('created_at', 'ai_workspace_files_created_at_index');
            $table->index('uploaded_by', 'ai_workspace_files_uploaded_by_foreign');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('workspace_id')->references('id')->on('ai_workspaces')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_workspace_files');
    }
};
