<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('documents')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'type')) {
                $table->string('type')->default('docs')->after('title');
            }
        });

        if (Schema::hasColumn('documents', 'type')) {
            DB::table('documents')->whereNull('type')->update(['type' => 'docs']);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('documents')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
