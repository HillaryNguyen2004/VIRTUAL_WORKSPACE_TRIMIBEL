<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lstm_predictions', function (Blueprint $table) {
            $table->string('predicted_level', 10)->default('Medium')->after('predicted_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lstm_predictions', function (Blueprint $table) {
            $table->dropColumn('predicted_level');
        });
    }
};
