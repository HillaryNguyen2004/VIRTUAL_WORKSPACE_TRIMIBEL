<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('title');
            $table->string('type')->default('docs');
            $table->string('html_path');
            $table->longText('searchable_text')->nullable();
            $table->string('docx_path')->nullable();
            $table->string('xlsx_path')->nullable();
            $table->string('pptx_path')->nullable();
            $table->unsignedBigInteger('last_edited_by')->nullable();
            $table->timestamps();
            $table->index('owner_id', 'documents_owner_id_foreign');
            $table->index('last_edited_by', 'documents_last_edited_by_foreign');
            $table->foreign('last_edited_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
