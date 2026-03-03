# WhatsApp Integration - Quick Reference

## 🚀 Quick Start

```bash
# 1. Run migrations
php artisan migrate

# 2. Add to .env
WHATSAPP_WABA_ID=xxx
WHATSAPP_PHONE_NUMBER_ID=xxx
WHATSAPP_ACCESS_TOKEN=xxx
WHATSAPP_VERIFY_TOKEN=xxx

# 3. Seed sample data
php artisan db:seed --class=WhatsAppSeeder

# 4. Set webhook in Meta Business Manager
# Callback URL: https://yourdomain.com/api/webhooks/whatsapp
# Verify Token: Your WHATSAPP_VERIFY_TOKEN
```

---

## 📋 Key Endpoints

### Team Inbox
```
GET /api/chat/whatsapp/inbox
    ?stage=thinking
    &assigned_to=2
    &unassigned=true
    &search="+1234567890"
    &per_page=20
```

### Send Messages
```
POST /api/chat/whatsapp/customers/{id}/reply
Body: { "content": "...", "type": "text" }

POST /api/chat/whatsapp/customers/{id}/template
Body: { "template_id": 5, "variables": {...} }

POST /api/chat/whatsapp/customers/{id}/canned-reply
Body: { "shortcut": "hi" }
```

### Customer Management
```
GET  /api/chat/whatsapp/customers/{id}/details
GET  /api/chat/whatsapp/customers/{id}/messages
PATCH /api/chat/whatsapp/customers/{id}
     Body: { stage, notes, tags, next_follow_up_at }
POST /api/chat/whatsapp/customers/{id}/assign
     Body: { user_id }
```

### Follow-ups
```
GET /api/chat/whatsapp/follow-ups
    ?my_follow_ups=true
    &overdue=true
    &today=true
    &status=pending

POST /api/chat/whatsapp/customers/{id}/follow-up
     Body: { scheduled_at, reason, notes, assign_to_user_id }

PATCH /api/chat/whatsapp/follow-ups/{id}/complete
```

### Admin - Settings
```
GET  /api/whatsapp-admin/settings
PUT  /api/whatsapp-admin/settings
     Body: { business_name, waba_id, phone_number_id, ... }
```

### Admin - Canned Replies
```
GET    /api/whatsapp-admin/canned-replies
       ?category=greeting&search=hi
POST   /api/whatsapp-admin/canned-replies
       Body: { shortcut, title, body, category, is_active }
PUT    /api/whatsapp-admin/canned-replies/{id}
DELETE /api/whatsapp-admin/canned-replies/{id}
```

### Admin - Templates
```
GET    /api/whatsapp-admin/templates
       ?status=approved&category=UTILITY&language=en
POST   /api/whatsapp-admin/templates
       Body: { template_name, language, category, body, variables, example }
PUT    /api/whatsapp-admin/templates/{id}
DELETE /api/whatsapp-admin/templates/{id}
POST   /api/whatsapp-admin/templates/{id}/sync
```

---

## 🗂️ Database Tables

| Table | Purpose |
|-------|---------|
| `whatsapp_customers` | Customer data + pipeline (new→won) |
| `whatsapp_conversations` | Links WhatsApp to app Conversation |
| `whatsapp_message_statuses` | Message delivery/read tracking |
| `whatsapp_follow_ups` | Follow-up tasks |
| `canned_replies` | Quick internal replies |
| `whatsapp_message_templates` | Meta-approved templates |
| `whatsapp_settings` | Config (credentials) |

---

## 🔄 Message Flow

**Incoming:**
```
Customer WhatsApp → Meta Cloud API → Webhook → 
WHatsAppWebhookController.receive() → 
Create WhatsAppCustomer → 
Create/Update WhatsAppConversation → 
Create Message(platform=whatsapp, direction=in) → 
Open service_window → 
Broadcast MessageSent event
```

**Outgoing:**
```
Agent replies in /api/chat/whatsapp/customers/{id}/reply →
Check: isServiceWindowOpen()? →
YES: send free-form text
NO: require template instead →
WhatsAppService.sendMessage() / sendTemplate() →
Create Message(platform=whatsapp, direction=out) →
Store platform_message_id →
Broadcast to team
```

---

## 🔐 Service Window (24-hour rule)

- **Open**: Customer sends message → 24-hour window starts
- **Closed**: 24 hours pass → Can only use templates
- **Check with**: `$whatsappConv->isServiceWindowOpen()`
- **Open manually**: `$whatsappConv->openServiceWindow()`
- **Close manually**: `$whatsappConv->closeServiceWindow()`

---

## 📊 Pipeline Stages

| Stage | Meaning | Action |
|-------|---------|--------|
| `new` | Just contacted | Send greeting, gather info |
| `thinking` | Considering | Answer questions, send pricing |
| `quoted` | Got your offer | Nurture, remind of benefits |
| `made_up_mind` | Ready to buy | Process order, payment |
| `won` | Completed purchase | Thank you, follow up satisfaction |
| `come_back` | Lost but might return | Re-engagement campaign |
| `lost` | Not interested | Archive, maybe occasional check-in |

---

## 📅 Follow-up Workflow

```python
1. Agent opens conversation
2. Agent types message + sets stage
3. Agent clicks "Schedule Follow-up"
4. Fills: date, time, reason, notes, assign_to_user
5. System creates WhatsAppFollowUp with status=pending
6. Schedule runs daily - checks if scheduled_at <= now()
7. Shows in dashboard: overdue|today|upcoming
8. Agent marks complete → status=completed
```

---

## 🎯 Common Use Cases

### Reply to Customer (during service window)
```javascript
POST /api/chat/whatsapp/customers/5/reply
{
  "content": "Thanks for your message! How can I help?"
}
```

### Re-engage After Window Closes
```javascript
POST /api/chat/whatsapp/customers/5/template
{
  "template_id": 3,  // "order_reminder" template
  "variables": {
    "order_id": "12345",
    "amount": "$99.99"
  }
}
```

### Quick Greeting
```javascript
POST /api/chat/whatsapp/customers/5/canned-reply
{
  "shortcut": "hi"  // automatically sends: "Hello! Thanks for reaching out..."
}
```

### Assign to Team Member
```javascript
POST /api/chat/whatsapp/customers/5/assign
{
  "user_id": 2  // Sales rep John
}
```

### Schedule Follow-up
```javascript
POST /api/chat/whatsapp/customers/5/follow-up
{
  "scheduled_at": "2026-03-03 14:00:00",
  "reason": "check_ready_to_buy",
  "notes": "Customer seemed interested, follow up on pricing",
  "assign_to_user_id": 2
}
```

### Get Today's Follow-ups
```javascript
GET /api/chat/whatsapp/follow-ups?my_follow_ups=true&today=true
```

---

## 🛠️ Code Examples

### Send a Message
```php
use App\Services\WhatsAppService;
use App\Models\Message;

$whatsappService = new WhatsAppService();
$message = Message::create([...]);
$whatsappService->sendMessage(
    '+1234567890',
    'Hello customer!',
    $message
);
```

### Check Service Window
```php
$whatsappConv = $customer->conversations()->first();

if ($whatsappConv->isServiceWindowOpen()) {
    // Can send free-form
} else {
    // Must use template
}
```

### Update Customer Stage
```php
$customer->update([
    'stage' => 'quoted',
    'notes' => 'Sent pricing $999/month',
    'next_follow_up_at' => now()->addDays(2)
]);
```

### Create Canned Reply
```php
use App\Models\CannedReply;

CannedReply::create([
    'shortcut' => 'pricing',
    'title' => 'Pricing Info',
    'body' => 'Our plans start at $29/month...',
    'category' => 'info',
    'created_by_user_id' => auth()->id()
]);
```

---

## ⚠️ Important Notes

1. **Access Token**: Long-lived tokens expire after ~5 years. Use System User token in production.
2. **Service Window**: Default 24 hours. After customer's last message.
3. **Templates**: Must be approved by Meta before using. Status starts as 'pending'.
4. **Message Types**: Support text, image, document, audio, video, location, contact, reaction.
5. **Rate Limits**: Meta allows ~80 messages/second per WABA.
6. **Quality Rating**: Avoid blocks by maintaining HIGH rating. Monitor in Meta.

---

## 🧪 Testing

### Test Webhook Verification
```bash
curl "http://localhost:8000/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=test123"
# Should return: test123
```

### Simulate Incoming Message
You can test using Meta's webhook tester in Business Manager, or use a tool like Postman to POST to your webhook endpoint.

### Check Logs
```bash
tail -f storage/logs/laravel.log | grep -i whatsapp
```

---

## 📚 Files Created

| File | Purpose |
|------|---------|
| Migrations | `2026_03_02_000001_create_whatsapp_tables.php` |
| Models | 7 models in `app/Models/` |
| Controllers | 3 controllers in `app/Http/Controllers/Api/` |
| Service | `app/Services/WhatsAppService.php` |
| Routes | Added to `routes/api.php` |
| Seeder | `database/seeders/WhatsAppSeeder.php` |
| Docs | 4 guide documents |

---

## 🚨 Troubleshooting

| Issue | Solution |
|-------|----------|
| Webhook not receiving | Check URL is HTTPS + public + verify token is correct |
| Can't send reply | Check service window is open, or use template |
| Template status is pending | Template needs Meta approval - check Business Manager |
| Access token error | Token may have expired, regenerate in Meta |
| Messages not marked read | Check webhook for status events is configured |

---

## 📞 Next Steps

1. ✅ Read [WHATSAPP_SETUP_GUIDE.md](WHATSAPP_SETUP_GUIDE.md)
2. ✅ Read [WHATSAPP_IMPLEMENTATION_GUIDE.md](WHATSAPP_IMPLEMENTATION_GUIDE.md)
3. ⏳ Run migrations
4. ⏳ Configure .env
5. ⏳ Set webhook in Meta
6. ⏳ Build frontend UI
7. ⏳ Create canned replies & templates
8. ⏳ Train team
9. ⏳ Monitor & optimize

---

**Ready?** Start with: `php artisan migrate` 🚀
