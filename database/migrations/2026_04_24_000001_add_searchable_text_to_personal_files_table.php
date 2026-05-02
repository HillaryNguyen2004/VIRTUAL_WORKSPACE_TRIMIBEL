<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('personal_files')) {
            return;
        }

        Schema::table('personal_files', function (Blueprint $table): void {
            if (!Schema::hasColumn('personal_files', 'searchable_text')) {
                $table->longText('searchable_text')->nullable()->after('size');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('personal_files')) {
            return;
        }

        Schema::table('personal_files', function (Blueprint $table): void {
            if (Schema::hasColumn('personal_files', 'searchable_text')) {
                $table->dropColumn('searchable_text');
            }
        });
    }
};
