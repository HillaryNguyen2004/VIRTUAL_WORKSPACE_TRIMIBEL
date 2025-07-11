<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('company_hours', function (Blueprint $table) {
            $table->id();
            $table->time('start_at');
            $table->time('end_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_hours');
    }
};
