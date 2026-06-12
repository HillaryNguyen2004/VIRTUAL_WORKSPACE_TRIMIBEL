<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->longText('content');
            $table->timestamps();
        });

        // Deferred FK: campaigns.email_template_id -> email_templates.id
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreign('email_template_id')->references('id')->on('email_templates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
