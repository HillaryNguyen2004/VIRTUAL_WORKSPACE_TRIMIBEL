<?php
// database/migrations/xxxx_add_level_to_roles_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedInteger('level')->default(0)->index();
        });
    }

    public function down(): void {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};

