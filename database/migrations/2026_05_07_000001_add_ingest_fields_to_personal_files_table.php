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

        Schema::table('personal_files', function (Blueprint $table) {
            if (!Schema::hasColumn('personal_files', 'ingest_status')) {
                $table->enum('ingest_status', ['pending', 'processing', 'completed', 'failed'])
                      ->default('pending')
                      ->after('size');
            }
            if (!Schema::hasColumn('personal_files', 'ingest_error')) {
                $table->text('ingest_error')->nullable()->after('ingest_status');
            }
            if (!Schema::hasColumn('personal_files', 'chunk_count')) {
                $table->unsignedInteger('chunk_count')->default(0)->after('ingest_error');
            }
            if (!Schema::hasColumn('personal_files', 'ingested_at')) {
                $table->timestamp('ingested_at')->nullable()->after('chunk_count');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('personal_files')) {
            return;
        }

        Schema::table('personal_files', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('personal_files', 'ingest_status')  ? 'ingest_status'  : null,
                Schema::hasColumn('personal_files', 'ingest_error')   ? 'ingest_error'   : null,
                Schema::hasColumn('personal_files', 'chunk_count')    ? 'chunk_count'    : null,
                Schema::hasColumn('personal_files', 'ingested_at')    ? 'ingested_at'    : null,
            ]));
        });
    }
};
