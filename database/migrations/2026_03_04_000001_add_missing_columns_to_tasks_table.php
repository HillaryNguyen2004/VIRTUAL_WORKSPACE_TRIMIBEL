<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only proceed if tasks table exists
        if (!Schema::hasTable('tasks')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            // Add missing columns only if they don't exist
            if (!Schema::hasColumn('tasks', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('tasks', 'priority')) {
                $table->string('priority')->default('normal')->after('status');
            }
            
            if (!Schema::hasColumn('tasks', 'percentage')) {
                $table->integer('percentage')->default(0)->after('active');
            }
            
            if (!Schema::hasColumn('tasks', 'estimated_time')) {
                $table->float('estimated_time')->nullable()->after('percentage');
            }
            
            if (!Schema::hasColumn('tasks', 'score')) {
                $table->integer('score')->default(0)->nullable()->after('estimated_time');
            }
            
            if (!Schema::hasColumn('tasks', 'phase_id')) {
                $table->unsignedBigInteger('phase_id')->nullable()->after('score');
            }
            
            if (!Schema::hasColumn('tasks', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('phase_id');
            }
            
            // Add timestamps if they don't exist
            if (!Schema::hasColumn('tasks', 'created_at')) {
                $table->timestamps();
            }
            
            // Add foreign key constraints if columns exist
            if (Schema::hasColumn('tasks', 'project_id') && !Schema::hasColumn('tasks', 'project_id_foreign')) {
                try {
                    $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->onUpdate('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
            
            if (Schema::hasColumn('tasks', 'phase_id') && !Schema::hasColumn('tasks', 'phase_id_foreign')) {
                try {
                    $table->foreign('phase_id')->references('id')->on('phases')->onDelete('cascade')->onUpdate('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
            
            if (Schema::hasColumn('tasks', 'parent_id') && !Schema::hasColumn('tasks', 'parent_id_foreign')) {
                try {
                    $table->foreign('parent_id')->references('id')->on('tasks')->onDelete('cascade')->onUpdate('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeignIfExists('tasks_project_id_foreign');
            $table->dropForeignIfExists('tasks_phase_id_foreign');
            $table->dropForeignIfExists('tasks_parent_id_foreign');
            
            // Drop columns
            $table->dropColumn([
                'project_id',
                'priority',
                'percentage',
                'estimated_time',
                'score',
                'phase_id',
                'parent_id',
            ]);
        });
    }
};
