<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('task_id'); // Custom primary key
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->string('status')->default('pending');
            $table->date('due_date')->nullable();
            $table->boolean('active')->default(true);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
