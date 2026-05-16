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
        Schema::table('meeting_attendees', function (Blueprint $table) {
            // Add this after user_id or wherever makes sense
            $table->integer('speaker_tag')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('meeting_attendees', function (Blueprint $table) {
            $table->dropColumn('speaker_tag');
        });
    }
};
