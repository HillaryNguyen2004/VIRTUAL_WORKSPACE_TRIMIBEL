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
        Schema::table('company_hours', function (Blueprint $table) {
            // JSON field to store working days as array
            // e.g., ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
            $table->json('working_days')->nullable()->default(null)->after('end_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_hours', function (Blueprint $table) {
            $table->dropColumn('working_days');
        });
    }
};
