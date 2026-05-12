<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('tasks', 'project_id')) {
                $table->unsignedBigInteger('project_id')->after('id')->nullable();
                $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->onUpdate('cascade');
            }
            
            if (!Schema::hasColumn('tasks', 'phase_id')) {
                $table->unsignedBigInteger('phase_id')->after('project_id')->nullable();
                $table->foreign('phase_id')->references('id')->on('phases')->onDelete('cascade')->onUpdate('cascade');
            }
            
            if (!Schema::hasColumn('tasks', 'priority')) {
                $table->string('priority')->default('medium')->after('status');
            }
            
            if (!Schema::hasColumn('tasks', 'start_date')) {
                $table->date('start_date')->nullable()->after('priority');
            }
            
            if (!Schema::hasColumn('tasks', 'timestamps')) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['project_id', 'phase_id']);
            $table->dropColumnIfExists(['project_id', 'phase_id', 'priority', 'start_date', 'created_at', 'updated_at']);
        });
    }
};
