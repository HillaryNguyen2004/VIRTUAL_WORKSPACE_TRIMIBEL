<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_folders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('share_token', 80)->nullable();
            $table->boolean('share_link_enabled')->default('0');
            $table->timestamps();
            $table->unique(['user_id', 'parent_id', 'name']);
            $table->unique('share_token');
            $table->index('parent_id', 'personal_folders_parent_id_foreign');
            $table->foreign('parent_id')->references('id')->on('personal_folders')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_folders');
    }
};
