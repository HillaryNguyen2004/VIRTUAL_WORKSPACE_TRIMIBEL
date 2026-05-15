<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->date('birthday')->nullable();
            $table->timestamp('birthday_email_sent_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->string('api_token', 80)->nullable();
            $table->boolean('blocked')->default('0');
            $table->integer('login_attempts')->default('0');
            $table->string('user_profile_photo')->nullable();
            $table->unsignedBigInteger('team_leader_id')->nullable();
            $table->string('username');
            $table->string('face_image_path')->nullable();
            $table->string('google_email')->nullable();
            $table->text('google_access_token')->nullable();
            $table->text('google_refresh_token')->nullable();
            $table->boolean('is_google_connected')->default('0');
            $table->text('face_hash')->nullable();
            $table->timestamps();
            $table->unique('email');
            $table->unique('username');
            $table->unique('api_token');
            $table->index('department_id', 'users_department_id_foreign');
            $table->index('team_leader_id', 'users_team_leader_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
