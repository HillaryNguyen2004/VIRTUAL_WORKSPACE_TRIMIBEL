# WhatsApp Integration Setup Guide

## Overview

This WhatsApp integration allows you to manage customer conversations through Meta's WhatsApp Business Platform, integrated with your existing chat system.

## Step-by-Step Setup

### 1. Create Meta Business Account

1. Go to https://business.facebook.com
2. Create a new Business Manager account
3. Complete business verification (recommended for production)

### 2. Create WhatsApp Business App

1. Go to https://developers.facebook.com
2. Create a new app (select "Business" type)
3. Add WhatsApp product
4. Go to "Getting Started" to retrieve:
   - **WABA_ID**: WhatsApp Business Account ID
   - **PHONE_NUMBER_ID**: Your business phone number ID
   - **Access Token**: Long-lived token (for production, use system user token)

### 3. Configure WhatsApp Webhook

Your webhook URL will be:
```
https://yourdomain.com/api/webhooks/whatsapp
```

In Meta Webhooks settings:
- **Callback URL**: `https://yourdomain.com/api/webhooks/whatsapp`
- **Verify Token**: Generate a secure random token (store in `.env`)
- **Subscribe to**: `messages` and `message_status`

### 4. Store WhatsApp Credentials

Update your `.env` file:
```env
WHATSAPP_WABA_ID=your_waba_id
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_VERIFY_TOKEN=your_verify_token
```

Or use the API to configure:
```bash
POST /api/whatsapp-admin/settings
{
    "business_name": "My Business",
    "waba_id": "xxxx",
    "phone_number_id": "xxxx",
    "phone_number": "+1234567890",
    "access_token": "xxxx",
    "verify_token": "xxxx",
    "webhook_url": "https://yourdomain.com/api/webhooks/whatsapp"
}
```

### 5. Run Migrations

```bash
php artisan migrate
```

This creates all necessary tables:
- `whatsapp_customers` - Customer data with pipeline stages
- `whatsapp_conversations` - Link between WhatsApp and app conversations
- `whatsapp_message_statuses` - Message delivery/read status tracking
- `canned_replies` - Quick reply templates
- `whatsapp_message_templates` - Meta-approved message templates
- `whatsapp_follow_ups` - Follow-up task management
- `whatsapp_settings` - WhatsApp configuration

## Key Concepts

### Service Window (24-hour rule)

After a customer messages you, there's a **24-hour service window** where you can send free-form replies. Outside this window, you must use Meta-approved templates.

**In your app:**
- Customer sends message → `service_window_status` = "open" + `service_window_opened_at` timestamp
- Check `is ServiceWindowOpen()` before allowing free-form replies
- If window is closed, require template message

### Pipeline Stages

Organize customers by status:
- **new**: Just contacted
- **thinking**: Asking questions
- **quoted**: You sent price/offer
- **made_up_mind**: Ready to buy
- **won**: Purchased/completed
- **come_back**: Lost but may return
- **lost**: Not interested

### Follow-ups

Schedule follow-up activities:
1. Team member sets `next_follow_up_at` on customer
2. Dashboard shows overdue + today's follow-ups
3. Send reminder via template (outside service window)
4. Mark as completed when done

## API Usage

### Team Inbox

```bash
# Get all open conversations (with filters)
GET /api/chat/whatsapp/inbox
    ?stage=thinking&assigned_to=2&unassigned=false&search=+1234567890

# Response includes:
{
    "success": true,
    "data": [
        {
            "id": 1,
            "customer": { "id": 1, "phone": "+1234567890", "stage": "thinking", ... },
            "conversation": { "messages": [...], ... },
            "is_open": true,
            "service_window_status": "open",
            "last_message_at": "2026-03-02T10:00:00Z"
        }
    ]
}
```

### Get Customer Details

```bash
GET /api/chat/whatsapp/customers/{customer_id}/details
```

### Send Free-form Reply (during service window)

```bash
POST /api/chat/whatsapp/customers/{customer_id}/reply
{
    "content": "Thanks for your message!",
    "type": "text"
}
```

### Send Template Message (outside service window)

```bash
POST /api/chat/whatsapp/customers/{customer_id}/template
{
    "template_id": 5,
    "variables": {
        "name": "John",
        "order_id": "12345"
    }
}
```

### Use Canned Reply (quick reply)

```bash
POST /api/chat/whatsapp/customers/{customer_id}/canned-reply
{
    "shortcut": "hi"  // Uses stored reply body
}
```

### Schedule Follow-up

```bash
POST /api/chat/whatsapp/customers/{customer_id}/follow-up
{
    "scheduled_at": "2026-03-03 14:00:00",
    "reason": "remind_quote",
    "notes": "Check if ready to purchase",
    "assign_to_user_id": 2
}
```

### Get Follow-ups Dashboard

```bash
# Today's follow-ups for current user
GET /api/chat/whatsapp/follow-ups?my_follow_ups=true&today=true

# All overdue follow-ups
GET /api/chat/whatsapp/follow-ups?overdue=true

# Get specific status
GET /api/chat/whatsapp/follow-ups?status=pending
```

## Admin APIs

### Manage Canned Replies

```bash
# List all
GET /api/whatsapp-admin/canned-replies?category=greeting&search=hi

# Create
POST /api/whatsapp-admin/canned-replies
{
    "shortcut": "hi",
    "title": "Greeting",
    "body": "Hello! Thanks for reaching out. How can I help?",
    "category": "greeting",
    "is_active": true
}

# Update
PUT /api/whatsapp-admin/canned-replies/{id}
{
    "title": "New title",
    "body": "New body..."
}

# Delete
DELETE /api/whatsapp-admin/canned-replies/{id}
```

### Manage Message Templates

```bash
# List (with filters)
GET /api/whatsapp-admin/templates?status=approved&category=UTILITY

# Create
POST /api/whatsapp-admin/templates
{
    "template_name": "order_confirmation",
    "language": "en",
    "category": "UTILITY",
    "body": "Your order {{1}} has been confirmed. Total: {{2}}",
    "variables": ["order_id", "total"],
    "example": "Your order #12345 has been confirmed. Total: $99.99"
}

# Update
PUT /api/whatsapp-admin/templates/{id}
{
    "body": "Updated template...",
    "is_active": true
}

# Delete (only non-approved)
DELETE /api/whatsapp-admin/templates/{id}
```

## Frontend UI Components Needed

### 1. Team Inbox
- List conversations sorted by last_message_at
- Filters: stage, assigned_to, unassigned, search
- Quick actions: Assign to me, Close conversation
- Shows unread count, last message preview

### 2. Conversation View
- Message timeline (with blue/gray for in/out)
- Message status badges (sent, delivered, read, failed)
- Customer panel showing:
  - Name, phone, stage
  - Tags / notes
  - Next follow-up date
  - Assignment
- Quick action buttons

### 3. Reply Input
- Detect service window status
- If open: Show text area for free-form
- If closed: Show template/canned reply selector instead
- Show message counter + template variables helper

### 4. Templates Dropdown
- List approved templates by category
- Show variables input fields
- Preview rendered message

### 5. Canned Replies
- Searchable shortcuts (e.g., "hi", "pricing")
- QA buttons / autocomplete
- Show on keyboard shortcut (e.g., Ctrl+/)

### 6. Follow-up Dashboard
- Three sections:
  - **Overdue** (red badge)
  - **Today** (yellow badge)
  - **Upcoming** (gray)
- Bulk actions: mark done, reschedule
- Create follow-up modal

### 7. Customer Edit Modal
- Stage dropdown
- Tags editor
- Notes textarea
- Next follow-up picker
- Assign to user dropdown

## Broadcasting / Real-time

When a message arrives or team member sends, broadcast event:
```php
broadcast(new MessageSent($message, $conversation))->toOthers();
```

Subscribe on frontend to channel:
```javascript
window.Echo.channel(`conversation.${conversationId}`)
    .listen('MessageSent', (data) => {
        // Add message to timeline
        // Update unread count
        // Play notification sound
    });
```

## Message Types Supported

- **text**: Regular message
- **image**: Image with optional caption
- **document**: File attachment
- **audio**: Voice message
- **video**: Video message
- **location**: Location pin
- **contact**: Contact card
- **reaction**: Emoji reaction

## File Handling

Incoming files are extracted and stored:
- Media files are stored in `storage/app/whatsapp/`
- URL is generated for display
- File metadata (size, type) is saved in message

Outgoing files:
- Must be publicly accessible via HTTPS URL
- Pass URL to `sendImage()` or `sendFile()` methods
- Meta downloads and delivers to customer

## Rate Limiting

Meta has these limits:
- **Message rate**: ~80 messages/second per WABA
- **Template messaging**: No limit for approved templates
- **Quality rating**: Maintain HIGH rating (avoid blocks)

Your app should:
- Queue messages to respect rate limits
- Retry failed messages with exponential backoff
- Monitor quality rating

## Troubleshooting

### Webhook not receiving messages
1. Check `WHATSAPP_VERIFY_TOKEN` is correct
2. Ensure webhook URL is publicly accessible
3. Check CloudFlare/nginx isn't blocking
4. Review Meta logs: Business Manager → WhatsApp → Logs

### Messages not sending
1. Check access token is valid (30-day expiry)
2. Verify phone number is approved in Meta
3. Check service window status (may need template)
4. Review error message in WhatsAppMessageStatus

### Service window not opening
1. Webhook must mark `service_window_opened_at ` when customer message arrives
2. Check `isServiceWindowOpen()` math: now < `service_window_opened_at` + 24 hours

## Security Considerations

1. **Never log** access tokens - they're already hidden in models
2. **Verify webhook signatures** - validate Meta's requests
3. **Rate limit** API endpoints to prevent abuse
4. **Validate** phone numbers in E.164 format: `+1234567890`
5. **Encrypt** customer data at rest if handling PII
6. **Use long-lived tokens** in production
7. **Monitor** for quality rating drops

## Pricing (as of July 2025)

- **Inbound messages**: Free
- **Outbound (service window)**: Free
- **Outbound (template)**: $0.05 per message (varies by region/category)
- **Template manager**: Free

Monitor costs by checking Meta billing.

## Next Steps

1. ✅ Set up WhatsApp Business Account
2. ✅ Configure webhook & credentials
3. ✅ Run migrations
4. ⏳ Create canned replies
5. ⏳ Create & approve templates
6. ⏳ Build frontend UI components
7. ⏳ Train team on using inbox
8. ⏳ Monitor quality rating & adjust if needed
