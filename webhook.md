# Webhooks in Lightpack Framework

## What is a Webhook?
A **webhook** is a way for external systems to notify your application in real-time when certain events occur. Instead of polling for changes, the external service sends an HTTP request (usually POST) to a URL you provide, containing details about the event.

---

## How Webhooks Work

1. **Event Occurs**  
   An event happens in the source system (e.g., payment received, user created).
2. **HTTP Request Sent**  
   The source system sends a POST request with event data (usually JSON) to your webhook endpoint (URL).
3. **Your App Receives the Request**  
   Your app's route/controller receives the request and processes the data.
4. **Acknowledge Receipt**  
   Your endpoint returns a 2xx HTTP status code to confirm receipt. If not, the source may retry.

---

## Webhook Support in Lightpack: Technical Plan

### Core Goals
- Allow apps to easily create webhook endpoints (routes/controllers)
- Provide helpers for payload parsing, signature verification, and logging
- Ensure security and idempotency
- Make it easy to extend for async processing or retries

### Architecture Overview

**Key Components:**
1. **WebhookController**  
   Handles incoming webhook requests (generic or per-provider).
2. **WebhookService**  
   - Verifies signatures (if configured)
   - Parses payloads
   - Logs events (optionally to DB or file)
   - Optionally queues events for async processing
3. **Route Registration**  
   - Simple way to register webhook endpoints (e.g., `/webhooks/stripe`, `/webhooks/github`)
4. **Config Support**  
   - Store secrets, endpoint configs, and logging preferences
5. **(Optional) WebhookEvent Model**  
   - Store received events for debugging/auditing

### Implementation Roadmap

1. **Directory Structure**
   - `src/Framework/Webhook/`
     - `WebhookController.php`
     - `WebhookService.php`
     - `WebhookEvent.php` (optional, for logging)
     - `helpers.php` (optional)

2. **Basic Controller**
   - Accepts POST requests, reads payload, calls service for verification and processing.

3. **Service Class**
   - Handles signature verification (configurable per provider)
   - Parses JSON payload
   - Logs event (to file or DB)
   - Returns 2xx or 4xx response

4. **Config File**
   - `config/webhook.php` for secrets, allowed providers, logging options.

5. **Route Example**
   ```php
   // routes/web.php
   $router->post('/webhooks/stripe', [WebhookController::class, 'handleStripe']);
   $router->post('/webhooks/github', [WebhookController::class, 'handleGithub']);
   ```

6. **Usage Example**
   - User creates a controller method for each provider, or uses a generic handler with provider detection.

---

## Typical Use Cases
- Payment notifications (Stripe, PayPal)
- Code repository events (GitHub, GitLab)
- Messaging and chat (Slack, Discord)
- E-commerce order/shipment updates
- Form submissions (Typeform, Google Forms)

---

## Key Concepts
- **Endpoint:** The URL in your app that receives webhook requests.
- **Payload:** The data sent (usually JSON) describing the event.
- **Secret/Signature:** Many providers sign requests so you can verify authenticity.
- **Retries:** If your endpoint fails, the provider may retry delivery.
- **Idempotency:** Your handler should safely handle duplicate events.

---

## Security Best Practices
- **Validate the signature** (if provided) to ensure the request is from a trusted source.
- **Restrict allowed methods** (POST only).
- **Rate limit** to prevent abuse.
- **Log all received events** for debugging and auditing.

---

## Example: Receiving a Webhook in PHP
```php
// In your controller
$data = file_get_contents('php://input');
$event = json_decode($data, true);

// Optionally: verify signature here

// Process the event
if ($event['type'] === 'user.created') {
    // Handle the event, e.g., create user in your DB
}

http_response_code(200); // Acknowledge receipt
```

---

## Planning Webhook Integration in Lightpack
When integrating webhook support, consider:
- **Routing:** How to define webhook endpoints.
- **Verification:** How to verify signatures/secrets.
- **Processing:** How to queue/process events (immediate or async).
- **Logging:** How to log received events and errors.
- **Retries:** How to handle failed or duplicate events.

---

## Next Steps
- Decide which events you want to receive via webhooks.
- Set up secure endpoints in your Lightpack app.
- Implement verification and logging.
- Test with real webhook providers (Stripe, GitHub, etc.).

For advanced use, consider queuing webhook processing, adding a dashboard for received events, and supporting outgoing webhooks (your app notifying others).
