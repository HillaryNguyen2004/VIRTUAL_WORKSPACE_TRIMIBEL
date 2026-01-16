<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_id')->nullable()->after('created_by');
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
        });
    }
};
