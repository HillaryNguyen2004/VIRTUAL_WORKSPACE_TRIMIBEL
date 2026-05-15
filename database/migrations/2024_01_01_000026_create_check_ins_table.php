<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->string('working_hours')->nullable();
            $table->boolean('is_late')->default('0');
            $table->timestamps();
            $table->index('date', 'check_ins_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
