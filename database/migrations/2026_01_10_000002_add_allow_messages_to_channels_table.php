<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            // Whether members can post messages. If false, only admins may post.
            if (!Schema::hasColumn('channels', 'allow_messages')) {
                $table->boolean('allow_messages')->default(true)->after('conversation_id');
            }
        });
    }

    public function down()
    {
        Schema::table('channels', function (Blueprint $table) {
            if (Schema::hasColumn('channels', 'allow_messages')) {
                $table->dropColumn('allow_messages');
            }
        });
    }
};
