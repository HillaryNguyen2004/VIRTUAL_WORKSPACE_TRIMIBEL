<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_document_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->unsignedBigInteger('document_id');
            $table->string('name');
            $table->timestamps();
            $table->index('folder_id', 'personal_document_links_folder_id_foreign');
            $table->index('document_id', 'personal_document_links_document_id_foreign');
            $table->index(['user_id', 'folder_id'], 'personal_document_links_user_id_folder_id_index');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('folder_id')->references('id')->on('personal_folders')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_document_links');
    }
};
