<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CannedReply;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSettings;
use App\Models\User;

class WhatsAppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update WhatsApp settings with demo values
        // IMPORTANT: Replace these with actual values from Meta
        WhatsAppSettings::updateOrCreate(
            ['id' => 1],
            [
                'business_name' => 'Demo Business',
                'waba_id' => env('WHATSAPP_WABA_ID', 'your_waba_id'),
                'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', 'your_phone_number_id'),
                'phone_number' => env('WHATSAPP_PHONE_NUMBER', '+1234567890'),
                'access_token' => env('WHATSAPP_ACCESS_TOKEN', 'your_access_token'),
                'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'your_verify_token'),
                'webhook_url' => env('WHATSAPP_WEBHOOK_URL', null),
                'is_active' => true
            ]
        );

        // Create common canned replies
        $cannedReplies = [
            [
                'shortcut' => 'hi',
                'title' => 'Greeting',
                'body' => 'Hello! 👋 Thanks for reaching out. How can I help you today?',
                'category' => 'greeting'
            ],
            [
                'shortcut' => 'pricing',
                'title' => 'Pricing Info',
                'body' => 'Our pricing depends on your needs. Could you tell me more about what you\'re looking for? I\'d be happy to provide a custom quote.',
                'category' => 'info'
            ],
            [
                'shortcut' => 'hours',
                'title' => 'Business Hours',
                'body' => 'Our business hours are Monday-Friday 9:00 AM - 6:00 PM EST. How can I assist you?',
                'category' => 'info'
            ],
            [
                'shortcut' => 'thanks',
                'title' => 'Thank You',
                'body' => 'Thank you for your interest! I\'ll get back to you shortly with more information. 😊',
                'category' => 'closing'
            ],
            [
                'shortcut' => 'confirm',
                'title' => 'Order Confirmation',
                'body' => 'Great! We\'ve received your order. You\'ll get a confirmation email shortly with tracking details.',
                'category' => 'order'
            ],
            [
                'shortcut' => 'support',
                'title' => 'Support Help',
                'body' => 'For technical support, please visit our help center at support.example.com or reply here with your issue.',
                'category' => 'support'
            ],
            [
                'shortcut' => 'callback',
                'title' => 'Call Me Back',
                'body' => 'I\'d love to chat! What\'s the best time to call you back?',
                'category' => 'engagement'
            ],
        ];

        $adminUser = User::first() ?? User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com'
        ]);

        foreach ($cannedReplies as $reply) {
            CannedReply::updateOrCreate(
                ['shortcut' => $reply['shortcut']],
                [
                    'title' => $reply['title'],
                    'body' => $reply['body'],
                    'category' => $reply['category'],
                    'created_by_user_id' => $adminUser->id,
                    'is_active' => true
                ]
            );
        }

        // Create sample templates (these need Meta approval before using)
        $templates = [
            [
                'template_name' => 'order_confirmation',
                'language' => 'en',
                'category' => 'UTILITY',
                'body' => 'Hi {{1}}, your order #{{2}} has been confirmed. Total amount: {{3}}. Expected delivery: {{4}}',
                'variables' => ['customer_name', 'order_id', 'total_amount', 'delivery_date'],
                'example' => 'Hi John, your order #12345 has been confirmed. Total amount: $99.99. Expected delivery: March 5, 2026'
            ],
            [
                'template_name' => 'shipping_update',
                'language' => 'en',
                'category' => 'UTILITY',
                'body' => 'Your order #{{1}} is on its way! Tracking: {{2}}. Track your package here: {{3}}',
                'variables' => ['order_id', 'tracking_number', 'tracking_url'],
                'example' => 'Your order #12345 is on its way! Tracking: 1Z999AA1234567890. Track your package here: https://example.com/track/12345'
            ],
            [
                'template_name' => 'delivery_confirmation',
                'language' => 'en',
                'category' => 'UTILITY',
                'body' => 'Great news! Your order #{{1}} has been delivered. Thanks for shopping with us! 🎉',
                'variables' => ['order_id'],
                'example' => 'Great news! Your order #12345 has been delivered. Thanks for shopping with us! 🎉'
            ],
            [
                'template_name' => 'payment_reminder',
                'language' => 'en',
                'category' => 'UTILITY',
                'body' => 'Hi {{1}}, this is a friendly reminder that your invoice {{2}} is due on {{3}}. Please pay to avoid late fees.',
                'variables' => ['customer_name', 'invoice_id', 'due_date'],
                'example' => 'Hi John, this is a friendly reminder that your invoice INV-12345 is due on March 10, 2026. Please pay to avoid late fees.'
            ],
            [
                'template_name' => 'survey',
                'language' => 'en',
                'category' => 'MARKETING',
                'body' => 'Hi {{1}}, we\'d love your feedback! Would you rate your experience with us? {{2}}',
                'variables' => ['customer_name', 'survey_link'],
                'example' => 'Hi John, we\'d love your feedback! Would you rate your experience with us? https://example.com/survey/12345'
            ],
            [
                'template_name' => 'promotional',
                'language' => 'en',
                'category' => 'MARKETING',
                'body' => '🎉 Exclusive offer for you! Get {{1}} off your next purchase. Use code: {{2}} Expires {{3}}',
                'variables' => ['discount', 'promo_code', 'expiry_date'],
                'example' => '🎉 Exclusive offer for you! Get 20% off your next purchase. Use code: SAVE20 Expires March 31, 2026'
            ],
        ];

        foreach ($templates as $template) {
            WhatsAppMessageTemplate::updateOrCreate(
                [
                    'template_name' => $template['template_name'],
                    'language' => $template['language']
                ],
                [
                    'category' => $template['category'],
                    'body' => $template['body'],
                    'variables' => $template['variables'],
                    'example' => $template['example'],
                    'created_by_user_id' => $adminUser->id,
                    'status' => 'pending', // Will need Meta approval
                ]
            );
        }

        $this->command->info('WhatsApp configuration seeded successfully!');
        $this->command->warn('⚠️  UPDATE YOUR .env WITH ACTUAL WHATSAPP CREDENTIALS:');
        $this->command->line('WHATSAPP_WABA_ID=your_waba_id');
        $this->command->line('WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id');
        $this->command->line('WHATSAPP_PHONE_NUMBER=+1234567890');
        $this->command->line('WHATSAPP_ACCESS_TOKEN=your_access_token');
        $this->command->line('WHATSAPP_VERIFY_TOKEN=random_token_for_webhook');
    }
}
