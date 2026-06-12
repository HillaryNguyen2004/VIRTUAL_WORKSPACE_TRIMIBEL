<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_folder_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('folder_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shared_by');
            $table->string('permission', 20)->default('view');
            $table->timestamps();
            $table->unique(['folder_id', 'user_id']);
            $table->index('user_id', 'personal_folder_shares_user_id_foreign');
            $table->index('shared_by', 'personal_folder_shares_shared_by_foreign');
            $table->foreign('folder_id')->references('id')->on('personal_folders')->onDelete('cascade');
            $table->foreign('shared_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_folder_shares');
    }
};
