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
            $table->float('current_productivity')->default(0)->after('predicted_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lstm_predictions', function (Blueprint $table) {
            $table->dropColumn('current_productivity');
        });
    }
};
