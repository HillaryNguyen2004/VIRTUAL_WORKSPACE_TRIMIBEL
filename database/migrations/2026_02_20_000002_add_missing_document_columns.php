<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('documents')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('documents', 'title')) {
                $table->string('title')->nullable();
            }
            if (!Schema::hasColumn('documents', 'html_path')) {
                $table->string('html_path')->nullable();
            }
            if (!Schema::hasColumn('documents', 'docx_path')) {
                $table->string('docx_path')->nullable();
            }
            if (!Schema::hasColumn('documents', 'last_edited_by')) {
                $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('documents', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('documents')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'last_edited_by')) {
                $table->dropConstrainedForeignId('last_edited_by');
            }
            if (Schema::hasColumn('documents', 'docx_path')) {
                $table->dropColumn('docx_path');
            }
            if (Schema::hasColumn('documents', 'html_path')) {
                $table->dropColumn('html_path');
            }
            if (Schema::hasColumn('documents', 'title')) {
                $table->dropColumn('title');
            }
            if (Schema::hasColumn('documents', 'owner_id')) {
                $table->dropConstrainedForeignId('owner_id');
            }
            if (Schema::hasColumn('documents', 'created_at')) {
                $table->dropTimestamps();
            }
        });
    }
};
