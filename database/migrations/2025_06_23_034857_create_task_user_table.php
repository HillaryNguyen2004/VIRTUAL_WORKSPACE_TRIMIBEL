<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskUserTable extends Migration
{
    public function up()
    {
        Schema::create('task_user', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');

            $table->primary(['task_id', 'user_id']); // Composite primary key

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_user');
    }
}
