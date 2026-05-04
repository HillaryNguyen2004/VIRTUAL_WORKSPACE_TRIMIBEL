<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users.team_leader_id — queried heavily in filters and team member lookups
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_team_leader_id_index')) {
                $table->index('team_leader_id', 'users_team_leader_id_index');
            }
        });

        // tasks.parent_id — used in subtask queries; no FK was added for it
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'parent_id') && !$this->indexExists('tasks', 'tasks_parent_id_index')) {
                $table->index('parent_id', 'tasks_parent_id_index');
            }
        });

        // activity_logs.created_at — used in latest() / viewAllLogs sorts
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!$this->indexExists('activity_logs', 'activity_logs_created_at_index')) {
                $table->index('created_at', 'activity_logs_created_at_index');
            }
        });

        // check_ins.date — queried by date range in attendance stats
        Schema::table('check_ins', function (Blueprint $table) {
            if (!$this->indexExists('check_ins', 'check_ins_date_index')) {
                $table->index('date', 'check_ins_date_index');
            }
        });

        // day_off_requests.date + status — queried together in attendance stats
        Schema::table('day_off_requests', function (Blueprint $table) {
            if (!$this->indexExists('day_off_requests', 'day_off_requests_date_status_index')) {
                $table->index(['date', 'status'], 'day_off_requests_date_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', fn(Blueprint $t) => $t->dropIndexIfExists('users_team_leader_id_index'));
        Schema::table('tasks', fn(Blueprint $t) => $t->dropIndexIfExists('tasks_parent_id_index'));
        Schema::table('activity_logs', fn(Blueprint $t) => $t->dropIndexIfExists('activity_logs_created_at_index'));
        Schema::table('check_ins', fn(Blueprint $t) => $t->dropIndexIfExists('check_ins_date_index'));
        Schema::table('day_off_requests', fn(Blueprint $t) => $t->dropIndexIfExists('day_off_requests_date_status_index'));
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(\DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains('Key_name', $indexName);
    }
};
