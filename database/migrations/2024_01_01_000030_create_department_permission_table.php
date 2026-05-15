<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();
            $table->unique(['department_id', 'permission_id']);
            $table->index('permission_id', 'department_permission_permission_id_foreign');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_permission');
    }
};
