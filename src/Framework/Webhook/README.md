# Lightpack Webhook Receiver

A robust, flexible, and production-ready webhook processing framework for PHP/Lightpack projects. Easily receive, verify, store, and process webhooks from any provider (Stripe, GitHub, Slack, etc.) with full test coverage and extensibility.

---

## 🚀 Features
- **Provider-agnostic:** Plug in any webhook provider with simple config.
- **Signature verification:** Securely verify authenticity of incoming requests.
- **Idempotency:** Prevent duplicate event processing using provider+event ID.
- **Payload & header storage:** Audit, debug, and replay all webhook events.
- **Status tracking:** Track event lifecycle (`pending`, `processed`, `failed`).
- **Extensible handlers:** Easily add provider-specific logic via custom classes.
- **Comprehensive tests:** Bulletproof against real-world and edge cases.

---

## 📦 Installation

1. **Add migration file to your Lightpack project** (see `src/Framework/Webhook/`).
2. **Run the migration:**
   ```bash
   php lightpack migrate:up
   ```

---

## 🛠️ Configuration

In your config (e.g., `config/webhook.php`):

```php
return [
    'stripe' => [
        'secret' => 'your-stripe-webhook-secret',
        'algo' => 'hmac', // or 'static' for static secrets
        'id' => 'id', // field in payload for event ID
        'handler' => App\Webhooks\StripeWebhookHandler::class,
    ],
    'github' => [
        'secret' => 'your-github-secret',
        'algo' => 'hmac',
        'id' => 'delivery', // GitHub's event ID is in a header (see below)
        'handler' => App\Webhooks\GitHubWebhookHandler::class,
    ],
];
```

- `secret`: Your provider's webhook signing secret.
- `algo`: Signature verification algorithm (`hmac` or `static`).
- `id`: Field name in payload (or header) for unique event ID.
- `handler`: Custom handler class for provider-specific logic.

---

## 🧩 Usage

1. **Define your webhook route:**

```php
// routes/web.php
route()->post('/webhook/:provider', [WebhookController::class, 'handle']);
```

2. **Implement custom handlers as needed:**

```php
// app/Webhooks/StripeWebhookHandler.php
use Lightpack\Webhook\BaseWebhookHandler;

class StripeWebhookHandler extends BaseWebhookHandler
{
    public function verifySignature(): static
    {
        // Stripe-specific signature logic
        return $this;
    }

    public function handle(): Response
    {
        // Custom event processing
        return response()->text('processed');
    }
}
```

---

## 🛡️ Security & Idempotency

- **Signature verification**: All requests are verified using the configured secret and algorithm before processing.
- **Idempotency**: Duplicate events (same provider + event ID) are ignored, ensuring safe retries.
- **Missing event ID**: If a provider does not send an event ID, events are stored with `event_id = null` and idempotency is not enforced.

---

## 📝 Event Storage Schema

| Column       | Type     | Description                             |
|--------------|----------|-----------------------------------------|
| id           | bigint   | Primary key                             |
| provider     | varchar  | Provider name (e.g., 'stripe')          |
| event_id     | varchar  | Unique event ID (nullable)              |
| payload      | text     | Full event payload (array, JSON-cast)   |
| headers      | text     | All request headers (array, JSON-cast)  |
| status       | varchar  | Event status (`pending`, `processed`, `failed`) |
| received_at  | datetime | Timestamp of event receipt              |

---

## 🧠 Best Practices & Tips

- **Always specify the correct event ID field** for each provider in your config.
- **For providers with event IDs in headers** (e.g., GitHub), extend your handler to extract the ID from headers.
- **If a provider does not send an event ID**, be aware that idempotency is not enforced—duplicate events may be processed.
- **Extend BaseWebhookHandler** for provider-specific signature verification and event processing.
- **Log and monitor** webhook event statuses for production reliability.

---

## 👩‍💻 Example: Custom Handler for Header-Based Event ID

```php
class GitHubWebhookHandler extends BaseWebhookHandler
{
    public function storeEvent(?string $eventId): WebhookEvent
    {
        // GitHub's delivery ID is in a header
        $eventId = $this->request->header('X-GitHub-Delivery');
        return parent::storeEvent($eventId);
    }
}
```

---

## ❓ FAQ

**Q: What if my provider doesn't send a unique event ID?**
A: The event will be stored with `event_id = null`, and idempotency will not be enforced. You may want to generate your own unique key if needed.

**Q: Can I process multiple providers with different logic?**
A: Yes! Simply specify a different handler class per provider in your config.

**Q: How do I debug webhook failures?**
A: All events (including failed ones) are stored with full payload and headers. Check the `webhook_events` table for details.

---