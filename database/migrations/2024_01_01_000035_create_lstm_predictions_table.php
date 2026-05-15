<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lstm_predictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('predicted_score', 5, 2)->default('0.00');
            $table->string('predicted_level', 10)->default('Medium');
            $table->double('current_productivity')->default('0.00');
            $table->decimal('confidence', 5, 4)->default('0.0000');
            $table->longText('prediction_details')->nullable();
            $table->timestamp('predicted_at')->nullable();
            $table->timestamps();
            $table->unique('employee_id');
            $table->index('employee_id', 'lstm_predictions_employee_id_index');
            $table->index('predicted_at', 'lstm_predictions_predicted_at_index');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lstm_predictions');
    }
};
