<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lstm_predictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('predicted_score', 5, 2)->default(0.00); // 0.00 to 100.00
            $table->decimal('confidence', 5, 4)->default(0.0000); // 0.0000 to 1.0000
            $table->json('prediction_details')->nullable(); // Store additional prediction metadata
            $table->timestamp('predicted_at');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');

            // Index for faster lookups
            $table->index('employee_id');
            $table->index('predicted_at');

            // Unique constraint to prevent duplicate predictions per employee
            $table->unique('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lstm_predictions');
    }
};
