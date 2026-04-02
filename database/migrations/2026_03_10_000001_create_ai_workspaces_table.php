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
        Schema::create('ai_workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->enum('visibility', ['private', 'team', 'public'])->default('private');
            $table->string('folder_path');
            $table->integer('file_count')->default(0);
            $table->bigInteger('storage_size')->default(0); // in bytes
            $table->string('status')->default('active'); // active, archived, deleted
            $table->timestamp('last_ingested_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('visibility');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_workspaces');
    }
};
