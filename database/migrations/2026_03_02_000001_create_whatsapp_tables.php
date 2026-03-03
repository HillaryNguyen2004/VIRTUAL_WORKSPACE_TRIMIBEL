<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // WhatsApp customers table
        if (!Schema::hasTable('whatsapp_customers')) {
            Schema::create('whatsapp_customers', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 20)->unique(); // E.164 format: +1234567890
                $table->string('wa_id')->unique(); // WhatsApp unique ID
                $table->string('display_name')->nullable();
                $table->string('name')->nullable();
                $table->text('notes')->nullable();
                $table->json('tags')->nullable(); // ["VIP", "Late payment", ...]
                
                // Pipeline stages
                $table->enum('stage', [
                    'new',
                    'thinking',
                    'quoted',
                    'made_up_mind',
                    'won',
                    'come_back',
                    'lost'
                ])->default('new');
                
                $table->unsignedBigInteger('assigned_to_user_id')->nullable(); // Team member assigned
                $table->timestamp('last_contact_at')->nullable();
                $table->timestamp('next_follow_up_at')->nullable();
                $table->timestamps();
                
                $table->foreign('assigned_to_user_id')->references('id')->on('users')->onDelete('set null');
                $table->index('stage');
                $table->index('next_follow_up_at');
                $table->index('assigned_to_user_id');
            });
        }

        // Link WhatsApp conversations to app conversations
        if (!Schema::hasTable('whatsapp_conversations')) {
            Schema::create('whatsapp_conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id'); // Link to existing conversation
                $table->unsignedBigInteger('whatsapp_customer_id');
                $table->string('platform_conversation_id')->nullable(); // WhatsApp thread ID if applicable
                $table->boolean('is_open')->default(true);
                $table->timestamp('opened_at')->useCurrent();
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->enum('service_window_status', ['open', 'closed'])->default('closed');
                $table->timestamp('service_window_opened_at')->nullable();
                $table->timestamps();
                
                $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
                $table->foreign('whatsapp_customer_id')->references('id')->on('whatsapp_customers')->onDelete('cascade');
                $table->unique(['conversation_id', 'whatsapp_customer_id'], 'conv_wa_cust_unique');
                $table->index('is_open');
            });
        }

        // Store WhatsApp platform message IDs and statuses
        if (!Schema::hasTable('whatsapp_message_statuses')) {
            Schema::create('whatsapp_message_statuses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('message_id'); // Link to messages table
                $table->string('platform_message_id'); // WhatsApp message ID
                $table->enum('status', ['sent', 'delivered', 'read', 'failed'])->default('sent');
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->string('error_message')->nullable();
                $table->timestamps();
                
                $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
                $table->unique('platform_message_id');
                $table->index('status');
            });
        }

        // Canned replies (quick replies for service window)
        if (!Schema::hasTable('canned_replies')) {
            Schema::create('canned_replies', function (Blueprint $table) {
                $table->id();
                $table->string('shortcut', 50)->unique(); // e.g., "hi", "pricing", "hours"
                $table->string('title'); // Display name
                $table->text('body'); // The actual message
                $table->string('category')->nullable(); // greeting, info, support, etc.
                $table->unsignedBigInteger('created_by_user_id');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index('shortcut');
            });
        }

        // WhatsApp template messages (Meta-approved)
        if (!Schema::hasTable('whatsapp_message_templates')) {
            Schema::create('whatsapp_message_templates', function (Blueprint $table) {
                $table->id();
                $table->string('template_name'); // Name in Meta system
                $table->string('language', 10)->default('en'); // en, vi, etc.
                $table->enum('category', [
                    'MARKETING',
                    'AUTHENTICATION',
                    'UTILITY',
                    'SERVICE_UPDATE'
                ])->default('UTILITY');
                $table->text('body'); // Template text
                $table->text('example')->nullable(); // Example with variables filled
                $table->json('variables')->nullable(); // ["variable1", "variable2"]
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->string('rejection_reason')->nullable();
                $table->string('meta_template_id')->nullable(); // ID from Meta
                $table->string('quality_rating')->nullable(); // HIGH, MEDIUM, LOW from Meta
                $table->unsignedBigInteger('created_by_user_id');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['template_name', 'language'], 'tpl_name_lang_unique');
                $table->index('status');
            });
        }

        // Follow-up activities log
        if (!Schema::hasTable('whatsapp_follow_ups')) {
            Schema::create('whatsapp_follow_ups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('whatsapp_customer_id');
                $table->unsignedBigInteger('assigned_to_user_id');
                $table->timestamp('scheduled_at');
                $table->string('reason')->nullable(); // "remind_quote", "check_status", "follow_after_window", etc.
                $table->text('notes')->nullable();
                $table->enum('status', ['pending', 'completed', 'skipped', 'rescheduled'])->default('pending');
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('completed_by_user_id')->nullable();
                $table->timestamps();
                
                $table->foreign('whatsapp_customer_id')->references('id')->on('whatsapp_customers')->onDelete('cascade');
                $table->foreign('assigned_to_user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('completed_by_user_id')->references('id')->on('users')->onDelete('set null');
                $table->index('scheduled_at');
                $table->index('status');
            });
        }

        // WhatsApp Business Account settings
        if (!Schema::hasTable('whatsapp_settings')) {
            Schema::create('whatsapp_settings', function (Blueprint $table) {
                $table->id();
                $table->string('business_name')->default('My Business');
                $table->string('waba_id'); // WhatsApp Business Account ID
                $table->string('phone_number_id'); // Business phone number ID
                $table->string('phone_number', 20); // E.164 format
                $table->string('access_token'); // Long-lived token
                $table->string('verify_token'); // Webhook verify token
                $table->string('webhook_url')->nullable(); // Registered webhook URL
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable(); // Any additional config
                $table->timestamps();
            });
        }

        // Message table updates (add fields for WhatsApp and other platforms)
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'platform')) {
                $table->enum('platform', ['internal', 'whatsapp', 'email', 'sms'])->default('internal')->after('type');
            }
            if (!Schema::hasColumn('messages', 'direction')) {
                $table->enum('direction', ['in', 'out'])->default('out')->after('platform');
            }
            if (!Schema::hasColumn('messages', 'sent_by_user_id')) {
                $table->unsignedBigInteger('sent_by_user_id')->nullable()->after('user_id');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_follow_ups');
        Schema::dropIfExists('whatsapp_message_templates');
        Schema::dropIfExists('canned_replies');
        Schema::dropIfExists('whatsapp_message_statuses');
        Schema::dropIfExists('whatsapp_conversations');
        Schema::dropIfExists('whatsapp_customers');
        Schema::dropIfExists('whatsapp_settings');
        
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'platform')) {
                $table->dropColumn('platform');
            }
            if (Schema::hasColumn('messages', 'direction')) {
                $table->dropColumn('direction');
            }
            if (Schema::hasColumn('messages', 'sent_by_user_id')) {
                $table->dropColumn('sent_by_user_id');
            }
        });
    }
};
