# WhatsApp Integration Implementation Guide

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Quick Start](#quick-start)
4. [Database Schema](#database-schema)
5. [API Endpoints](#api-endpoints)
6. [Frontend Integration](#frontend-integration)
7. [Real-time Updates](#real-time-updates)
8. [Best Practices](#best-practices)

## Overview

This WhatsApp integration adds a complete **team inbox system** to your existing chat application. It allows your team to:

- **Receive & manage** customer messages from WhatsApp
- **Reply** within the 24-hour service window
- **Use templates** for messaging outside the service window
- **Organize customers** through a sales pipeline
- **Schedule follow-ups** and track them
- **Use quick replies** for faster responses

The system is built on top of your existing `Conversation` and `Message` models, extending them with WhatsApp-specific functionality.

## Architecture

### How Messages Flow

**Incoming (Customer → Your App):**
```
Customer sends WhatsApp message
    ↓
Meta's Cloud API sends webhook to: POST /api/webhooks/whatsapp
    ↓
WhatsAppWebhookController.receive() processes the message
    ↓
Create WhatsAppCustomer (if new)
    ↓
Create Conversation (direct type, linked to customer)
    ↓
Create Message with platform='whatsapp', direction='in'
    ↓
Open service_window (24-hour clock starts)
    ↓
Broadcast MessageSent event to team
    ↓
Team members see it in /inbox
```

**Outgoing (Your App → Customer):**
```
Agent types reply in UI
    ↓
POST /api/chat/whatsapp/customers/{id}/reply
    ↓
Check: is service_window_open()?
    IF YES: Send free-form via WhatsAppService.sendMessage()
    IF NO: Return error, require template instead
    ↓
Create Message with platform='whatsapp', direction='out'
    ↓
Send to Meta: Call WhatsAppService.sendTemplate()
    ↓
Store platform_message_id in WhatsAppMessageStatus
    ↓
Broadcast to team
    ↓
Update conversation.updated_at
```

### Key Objects

| Object | Purpose |
|--------|---------|
| `WhatsAppCustomer` | Stores customer data (phone, stage, assignee, notes) |
| `WhatsAppConversation` | Links WhatsApp conversation to app Conversation |
| `Message` | Existing model, extended with `platform`, `direction`, `sent_by_user_id` |
| `WhatsAppMessageStatus` | Tracks message delivery/read status from Meta |
| `WhatsAppFollowUp` | Schedule tasks to follow up with customers |
| `CannedReply` | Quick reply templates (internal, not Meta-approved) |
| `WhatsAppMessageTemplate` | Meta-approved templates for service window messages |
| `WhatsAppSettings` | Configuration (credentials, webhook URL) |

## Quick Start

### 1. Install & Configure

```bash
# Copy .env template
cp .env.whatsapp.example .env

# Add your Meta credentials to .env
# WHATSAPP_WABA_ID=...
# WHATSAPP_PHONE_NUMBER_ID=...
# WHATSAPP_ACCESS_TOKEN=...
# WHATSAPP_VERIFY_TOKEN=generate_random_string

# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed --class=WhatsAppSeeder
```

### 2. Set Webhook in Meta

In Meta Business Manager → WhatsApp → Webhooks:
- **Callback URL**: `https://yourdomain.com/api/webhooks/whatsapp`
- **Verify Token**: Use your `WHATSAPP_VERIFY_TOKEN` value
- **Subscribe to events**: `messages`, `message_status`

### 3. Verify Webhook

Meta will send a GET request to verify. Your app responds with the `hub_challenge` parameter.

```bash
# Test locally
curl "http://localhost:8000/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=test_challenge"
# Should return: test_challenge
```

### 4. Create Sample Canned Replies

```bash
POST /api/whatsapp-admin/canned-replies
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
    "shortcut": "hi",
    "title": "Greeting",
    "body": "Hello! Thanks for contacting us. How can I help?",
    "category": "greeting"
}
```

## Database Schema

### whatsapp_customers

Stores customer information and pipeline status:

```
id, phone (unique), wa_id (unique), name, display_name, notes (JSON tags)
stage: 'new|thinking|quoted|made_up_mind|won|come_back|lost'
assigned_to_user_id (FK to users)
last_contact_at, next_follow_up_at, timestamps
```

### whatsapp_conversations

Links WhatsApp conversations to app conversations:

```
id, conversation_id (FK), whatsapp_customer_id (FK)
is_open, opened_at, closed_at, last_message_at
service_window_status: 'open|closed'
service_window_opened_at (tracks when 24-hour window starts)
timestamps
```

### whatsapp_message_statuses

Tracks Meta delivery/read status:

```
id, message_id (FK), platform_message_id (unique)
status: 'sent|delivered|read|failed'
delivered_at, read_at, error_message, timestamps
```

### messages (extended)

Your existing messages table with WhatsApp fields:

```
... existing fields ...
platform: 'internal|whatsapp|email|sms'
direction: 'in|out'  (incoming from customer or outgoing from agent)
sent_by_user_id (FK, who sent it - for tracking agent replies)
```

### canned_replies

Quick internal reply templates (no Meta approval needed):

```
id, shortcut (unique), title, body, category
created_by_user_id (FK), is_active, timestamps
```

### whatsapp_message_templates

Meta-approved templates for service window messaging:

```
id, template_name, language, category
body, example, variables (JSON array)
status: 'pending|approved|rejected'
rejection_reason, meta_template_id, quality_rating
created_by_user_id, is_active, timestamps
UNIQUE: (template_name, language)
```

### whatsapp_follow_ups

Follow-up task management:

```
id, whatsapp_customer_id (FK), assigned_to_user_id (FK)
scheduled_at (when to follow up)
reason, notes, status: 'pending|completed|skipped|rescheduled'
completed_at, completed_by_user_id, timestamps
```

## API Endpoints

### Team Inbox

```
GET     /api/chat/whatsapp/inbox
        Query params: stage, assigned_to, unassigned, search, per_page
        Returns paginated conversations with customer & last message
```

### Messaging

```
POST    /api/chat/whatsapp/customers/{id}/reply
        Body: { content, type (opt) }
        → Sends free-form message (only if service window open)

POST    /api/chat/whatsapp/customers/{id}/template
        Body: { template_id, variables (opt) }
        → Sends Meta-approved template

POST    /api/chat/whatsapp/customers/{id}/canned-reply
        Body: { shortcut }
        → Uses quick reply shortcut
```

### Customer Management

```
GET     /api/chat/whatsapp/customers/{id}/details
        Returns customer with conversations & follow-ups

PATCH   /api/chat/whatsapp/customers/{id}
        Body: { stage, notes, tags, next_follow_up_at }

POST    /api/chat/whatsapp/customers/{id}/assign
        Body: { user_id }
        → Assign to team member
```

### Follow-ups

```
GET     /api/chat/whatsapp/follow-ups
        Query: my_follow_ups, overdue, today, status
        Returns list of follow-up tasks

POST    /api/chat/whatsapp/customers/{id}/follow-up
        Body: { scheduled_at, reason, notes, assign_to_user_id }

PATCH   /api/chat/whatsapp/follow-ups/{id}/complete
        Marks follow-up as done
```

### Admin: Manage Templates

```
GET     /api/whatsapp-admin/templates
        Query: status, category, language

POST    /api/whatsapp-admin/templates
        Body: { template_name, language, category, body, variables, example }
        ⚠️  Status starts as 'pending' - needs Meta approval!

PUT     /api/whatsapp-admin/templates/{id}
        Update template content

DELETE  /api/whatsapp-admin/templates/{id}
        Only delete if not approved
```

### Admin: Manage Canned Replies

```
GET     /api/whatsapp-admin/canned-replies
        Query: category, search

POST    /api/whatsapp-admin/canned-replies
        Body: { shortcut, title, body, category, is_active }

PUT     /api/whatsapp-admin/canned-replies/{id}

DELETE  /api/whatsapp-admin/canned-replies/{id}
```

## Frontend Integration

### 1. Team Inbox Screen

```vue
<template>
  <div class="whatsapp-inbox">
    <!-- Filters -->
    <div class="filters">
      <select v-model="filters.stage">
        <option value="">All Stages</option>
        <option value="new">New</option>
        <option value="thinking">Thinking</option>
        <!-- ... -->
      </select>
      <input v-model="filters.search" placeholder="Search phone/name">
      <button @click="getInbox">Search</button>
    </div>

    <!-- Conversation List -->
    <div class="conversation-list">
      <div 
        v-for="conv in conversations"
        :key="conv.id"
        class="conversation-item"
        :class="{ unread: conv.unread_count > 0 }"
        @click="selectConversation(conv)"
      >
        <div class="customer-name">{{ conv.customer.display_name }}</div>
        <div class="last-message">{{ conv.last_message?.content }}</div>
        <div class="time">{{ formatTime(conv.last_message_at) }}</div>
        <div class="unread-badge" v-if="conv.unread_count">
          {{ conv.unread_count }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const conversations = ref([])
const filters = ref({ stage: '', assigned_to: '', search: '' })

async function getInbox() {
  const res = await fetch('/api/chat/whatsapp/inbox?' + new URLSearchParams(filters.value), {
    headers: { Authorization: `Bearer ${token}` }
  })
  conversations.value = await res.json()
}

function selectConversation(conv) {
  // Open conversation view
}
</script>
```

### 2. Conversation View

```vue
<template>
  <div class="conversation-view">
    <!-- Customer Panel (Right) -->
    <div class="customer-panel">
      <h3>{{ customer.name }}</h3>
      <p>{{ customer.phone }}</p>
      
      <div class="stage">
        <label>Stage</label>
        <select v-model="customer.stage" @change="updateCustomer">
          <option value="new">New</option>
          <option value="thinking">Thinking</option>
          <!-- ... -->
        </select>
      </div>

      <div class="assigned">
        <label>Assigned to</label>
        <select @change="assign">
          <option v-for="user in users" :key="user.id" :value="user.id">
            {{ user.name }}
          </option>
        </select>
      </div>

      <div class="next-followup">
        <label>Next Follow-up</label>
        <input v-model="customer.next_follow_up_at" type="datetime-local">
        <button @click="scheduleFollowUp">Schedule</button>
      </div>

      <div class="notes">
        <label>Notes</label>
        <textarea v-model="customer.notes"></textarea>
        <button @click="updateCustomer">Save</button>
      </div>
    </div>

    <!-- Messages (Center) -->
    <div class="messages">
      <div v-for="msg in messages" :key="msg.id" class="message" :class="msg.direction">
        <div class="sender">{{ msg.user.name }}</div>
        <div class="content">{{ msg.content }}</div>
        <div class="status" v-if="msg.whatsapp_status">
          {{ msg.whatsapp_status.status }}
          <span v-if="msg.whatsapp_status.read_at">✓✓</span>
          <span v-else-if="msg.whatsapp_status.delivered_at">✓✓</span>
          <span v-else>✓</span>
        </div>
        <div class="time">{{ formatTime(msg.created_at) }}</div>
      </div>
    </div>

    <!-- Reply Input -->
    <div class="reply-input">
      <div v-if="serviceWindowOpen" class="window-status open">
        ✓ Service window open - send free-form messages
      </div>
      <div v-else class="window-status closed">
        ✗ Service window closed - use templates only
      </div>

      <!-- Free-form reply (if window open) -->
      <div v-if="serviceWindowOpen" class="reply-form">
        <textarea v-model="replyText" placeholder="Type your message..."></textarea>
        <button @click="sendReply">Send</button>
      </div>

      <!-- Template/Canned reply (if window closed) -->
      <div v-else class="template-selector">
        <select @change="selectTemplate">
          <option value="">Choose a template...</option>
          <option v-for="tpl in templates" :key="tpl.id" :value="tpl.id">
            {{ tpl.template_name }}
          </option>
        </select>
      </div>

      <!-- Quick reply buttons -->
      <div class="canned-replies">
        <button 
          v-for="reply in cannedReplies"
          :key="reply.id"
          @click="useCannedReply(reply.shortcut)"
        >
          {{ reply.title }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const customer = ref({})
const messages = ref([])
const replyText = ref('')
const serviceWindowOpen = ref(false)
const templates = ref([])
const cannedReplies = ref([])

onMounted(async () => {
  // Fetch customer, messages, templates, canned replies
  await fetchCustomer()
  await fetchMessages()
  await fetchTemplates()
  await fetchCannedReplies()
  
  // Subscribe to real-time updates
  Echo.channel(`conversation.${conversationId}`)
    .listen('MessageSent', (data) => {
      messages.value.push(data.message)
    })
})

async function sendReply() {
  if (!serviceWindowOpen.value) {
    alert('Service window closed - use templates')
    return
  }

  await fetch(`/api/chat/whatsapp/customers/${customerId}/reply`, {
    method: 'POST',
    body: JSON.stringify({ content: replyText.value }),
    headers: { Authorization: `Bearer ${token}` }
  })
  
  replyText.value = ''
}

async function useCannedReply(shortcut) {
  await fetch(`/api/chat/whatsapp/customers/${customerId}/canned-reply`, {
    method: 'POST',
    body: JSON.stringify({ shortcut }),
    headers: { Authorization: `Bearer ${token}` }
  })
}
</script>
```

### 3. Follow-up Dashboard

```vue
<template>
  <div class="follow-up-dashboard">
    <h2>Follow-ups</h2>

    <!-- Overdue -->
    <div class="section overdue" v-if="overdue.length">
      <h3>🔴 Overdue ({{ overdue.length }})</h3>
      <div v-for="fu in overdue" :key="fu.id" class="follow-up-item">
        <div class="customer">{{ fu.customer.name }}</div>
        <div class="reason">{{ fu.reason }}</div>
        <div class="scheduled">Scheduled: {{ formatDate(fu.scheduled_at) }}</div>
        <button @click="markComplete(fu.id)">Complete</button>
      </div>
    </div>

    <!-- Today -->
    <div class="section today" v-if="today.length">
      <h3>🟡 Today ({{ today.length }})</h3>
      <div v-for="fu in today" :key="fu.id" class="follow-up-item">
        <!-- ... -->
      </div>
    </div>

    <!-- Upcoming -->
    <div class="section upcoming" v-if="upcoming.length">
      <h3>⚪ Upcoming ({{ upcoming.length }})</h3>
      <div v-for="fu in upcoming" :key="fu.id" class="follow-up-item">
        <!-- ... -->
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

const allFollowUps = ref([])

const overdue = computed(() => allFollowUps.value.filter(fu => fu.isOverdue))
const today = computed(() => allFollowUps.value.filter(fu => fu.isDueToday))
const upcoming = computed(() => allFollowUps.value.filter(fu => !fu.isOverdue && !fu.isDueToday))

onMounted(async () => {
  const res = await fetch('/api/chat/whatsapp/follow-ups?status=pending')
  allFollowUps.value = await res.json()
})

async function markComplete(fuId) {
  await fetch(`/api/chat/whatsapp/follow-ups/${fuId}/complete`, {
    method: 'PATCH'
  })
  // Refresh
}
</script>
```

## Real-time Updates

When a message is sent/received, broadcast to all team members:

```js
// Listen for new messages
Echo.channel(`conversation.${conversationId}`)
  .listen('MessageSent', (data) => {
    console.log('New message:', data.message)
    // Add to messages array
    // Update unread count
    // Play notification
  })
```

## Best Practices

### 1. Service Window Management

```php
// Always check before sending free-form
if ($whatsappConv->canReplyFreeForm()) {
    // Send free-form text
} else {
    // Require template message
}
```

### 2. Error Handling

```php
try {
    $messageId = $whatsappService->sendMessage($phone, $content);
    // Store message ID for tracking
} catch (Exception $e) {
    Log::error('Failed to send: ' . $e->getMessage());
    // Notify user, maybe retry later
}
```

### 3. Follow-up Automation

```php
// In a scheduled command (runs every hour)
WhatsAppFollowUp::overdue()
    ->each(function ($followUp) {
        // Send reminder template to customer
        // Notify assigned agent
    });
```

### 4. Rate Limiting

```php
// In middleware or Job
if (Message::whereDate('created_at', today())->count() > 1000) {
    // Queue the message instead of sending immediately
}
```

### 5. Customer Privacy

- Don't log access tokens
- Encrypt stored customer data if required
- Validate phone numbers
- Respect opt-out requests

## Troubleshooting

### Webhook not receiving messages

1. Check `WHATSAPP_VERIFY_TOKEN` matches Meta setting
2. Ensure URL is publicly accessible: `https://yourdomain.com/api/webhooks/whatsapp`
3. Check firewall/CloudFlare isn't blocking
4. Test webhook in Meta Business Manager

### Service window not opening

- Check `openServiceWindow()` is called when customer message arrives
- Verify `service_window_opened_at` timestamp is recent

### Templates not sending

- Verify template is approved (status = 'approved')
- Check variables match template definition
- Review Meta logs for approval status

### Messages not marking as delivered/read

- WhatsApp status webhook must be configured
- Check `updateFromWebhook()` is being called
- Review webhook logs

---

For detailed step-by-step setup, see [WHATSAPP_SETUP_GUIDE.md](./WHATSAPP_SETUP_GUIDE.md)
