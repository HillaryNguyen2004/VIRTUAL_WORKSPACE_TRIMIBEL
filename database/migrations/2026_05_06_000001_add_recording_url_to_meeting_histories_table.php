<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('meeting_histories', function (Blueprint $table) {
            $table->string('recording_url')->nullable()->after('notes');
        });
    }

    public function down()
    {
        Schema::table('meeting_histories', function (Blueprint $table) {
            $table->dropColumn('recording_url');
        });
    }
};
