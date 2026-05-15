<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_off_requests', function (Blueprint $table) {
            $table->id();
            $table->char('request_group_id', 36)->nullable();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->enum('leave_type', ['OFF_FULL','OFF_HALF']);
            $table->enum('half_day_period', ['AM','PM'])->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['PENDING','APPROVED','REJECTED'])->default('PENDING');
            $table->timestamps();
            $table->unique(['user_id', 'date']);
            $table->index('request_group_id', 'idx_request_group_id');
            $table->index(['date', 'status'], 'day_off_requests_date_status_index');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_off_requests');
    }
};
