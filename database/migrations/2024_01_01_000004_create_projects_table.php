<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('staff_id');
            $table->enum('status', ['active','inactive'])->default('active');
            $table->integer('percentage')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->index(['staff_id', 'status'], 'projects_staff_id_status_index');
            $table->foreign('staff_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
