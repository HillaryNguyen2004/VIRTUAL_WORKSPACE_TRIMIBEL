<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('personal_files') || Schema::hasColumn('personal_files', 'document_id')) {
            return;
        }

        Schema::table('personal_files', function (Blueprint $table) {
            $table->foreignId('document_id')
                ->nullable()
                ->after('folder_id')
                ->constrained('documents')
                ->nullOnDelete();

            $table->index(['user_id', 'document_id']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('personal_files') || !Schema::hasColumn('personal_files', 'document_id')) {
            return;
        }

        Schema::table('personal_files', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->dropIndex(['user_id', 'document_id']);
            $table->dropColumn('document_id');
        });
    }
};
