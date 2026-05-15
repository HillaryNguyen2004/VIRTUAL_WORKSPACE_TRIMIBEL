<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedBigInteger('email_template_id')->nullable();
            $table->boolean('sent')->default('0');
            $table->timestamps();
            $table->index('email_template_id', 'campaigns_email_template_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
