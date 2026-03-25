<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_workspace_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('ai_workspaces')->onDelete('cascade');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->bigInteger('file_size'); // in bytes
            $table->integer('chunk_count')->default(0);
            $table->enum('ingest_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('ingest_error')->nullable();
            $table->timestamp('ingested_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('workspace_id');
            $table->index('ingest_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_workspace_files');
    }
};
