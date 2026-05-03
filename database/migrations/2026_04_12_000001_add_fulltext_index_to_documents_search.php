<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('documents')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        try {
            DB::statement('ALTER TABLE documents ADD FULLTEXT INDEX documents_search_fulltext_idx (title, searchable_text)');
        } catch (\Throwable $error) {
            // Ignore when index already exists or FULLTEXT is unsupported on current setup.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('documents')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        try {
            DB::statement('ALTER TABLE documents DROP INDEX documents_search_fulltext_idx');
        } catch (\Throwable $error) {
            // Ignore when index does not exist.
        }
    }
};
