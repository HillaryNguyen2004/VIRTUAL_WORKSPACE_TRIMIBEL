<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_folders', function (Blueprint $table): void {
            $table->string('share_token', 80)->nullable()->unique()->after('name');
            $table->boolean('share_link_enabled')->default(false)->after('share_token');
        });
    }

    public function down(): void
    {
        Schema::table('personal_folders', function (Blueprint $table): void {
            $table->dropColumn(['share_token', 'share_link_enabled']);
        });
    }
};
