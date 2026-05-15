<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->string('status')->default('');
            $table->string('priority')->default('normal');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('active')->default('1');
            $table->integer('percentage')->default('0');
            $table->float('estimated_time')->nullable();
            $table->integer('score')->nullable()->default('0');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('phase_id')->nullable();
            $table->bigInteger('parent_id')->nullable();
            $table->timestamps();
            $table->index('project_id', 'tasks_project_id_index');
            $table->index('phase_id', 'tasks_phase_id_index');
            $table->index('parent_id', 'tasks_parent_id_index');
            $table->foreign('phase_id')->references('id')->on('phases')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });

        // Deferred FK: task_read_statuses.task_id -> tasks.id
        Schema::table('task_read_statuses', function (Blueprint $table) {
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });

        // Deferred FK: task_user.task_id -> tasks.id
        Schema::table('task_user', function (Blueprint $table) {
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
