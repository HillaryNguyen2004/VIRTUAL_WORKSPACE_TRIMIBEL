<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_off_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('day_off_requests', 'request_group_id')) {
                $table->char('request_group_id', 36)->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('day_off_requests', 'half_day_period')) {
                $table->enum('half_day_period', ['AM', 'PM'])->nullable()->after('leave_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('day_off_requests', function (Blueprint $table) {
            $table->dropColumn(['request_group_id', 'half_day_period']);
        });
    }
};
