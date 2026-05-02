<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_folder_shares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('folder_id')->constrained('personal_folders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_by')->constrained('users')->cascadeOnDelete();
            $table->string('permission', 20)->default('view');
            $table->timestamps();

            $table->unique(['folder_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_folder_shares');
    }
};
