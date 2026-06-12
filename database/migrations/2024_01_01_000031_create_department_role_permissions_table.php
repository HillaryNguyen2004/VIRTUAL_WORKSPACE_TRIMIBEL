<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();
            $table->unique(['department_id', 'role_id', 'permission_id'], 'dept_role_perm_unique');
            $table->index('role_id', 'department_role_permissions_role_id_foreign');
            $table->index('permission_id', 'department_role_permissions_permission_id_foreign');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_role_permissions');
    }
};