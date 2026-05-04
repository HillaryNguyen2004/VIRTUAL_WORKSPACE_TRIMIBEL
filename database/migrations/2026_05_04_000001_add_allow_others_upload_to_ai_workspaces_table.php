<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_workspaces', function (Blueprint $table) {
            $table->boolean('allow_others_upload')->default(false)->after('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_workspaces', function (Blueprint $table) {
            $table->dropColumn('allow_others_upload');
        });
    }
};