<?php

namespace Lightpack\Webhook;

use Lightpack\Http\Response;
use Lightpack\Exceptions\HttpException;
use Lightpack\Http\Request;

abstract class BaseWebhookHandler
{
    /**
     * @param array $config   Webhook provider configuration (secret, headers, etc).
     * @param string $provider The provider key (e.g., 'stripe', 'github').
     */
    public function __construct(
        protected Request $request,
        protected array $config,
        protected string $provider
    ) {}

    /**
     * Handle the webhook request.
     *
     * This method must be implemented by subclasses to process the webhook payload
     * and return an HTTP response.
     *
     * @return Response HTTP response to be sent to the webhook provider
     */
    abstract public function handle(): Response;

    /**
     * Store the webhook event and ensure idempotency.
     *
     * @param string|null $eventId Unique event identifier from the provider (if available).
     * @return \Lightpack\Webhook\WebhookEvent Returns the stored event.
     * @throws \Lightpack\Exceptions\HttpException If a duplicate event is detected (idempotency).
     */
    public function storeEvent(?string $eventId): WebhookEvent
    {
        if ($eventId) {
            $this->enforceEventIdempotency($eventId);
        }

        return $this->storeNewWebhookEvent($eventId);
    }

    /**
     * Verify the webhook signature for authenticity.
     *
     * By default, this method checks the signature using HMAC or a static secret,
     * based on the provider configuration. Throws a 401 HttpException on failure.
     *
     * Override this method in subclasses for provider-specific signature logic.
     *
     * @throws \Lightpack\Exceptions\HttpException If the signature is invalid
     * @return void
     */
    public function verifySignature(): static
    {
        $verified = true;
        $header = $this->config['signature_header'] ?? 'X-Webhook-Signature';
        $providedSignature = $this->request->header($header);
        $secret = $this->config['secret'] ?? null;
        $algo = $this->config['algo'] ?? null;

        if (!$secret) {
            $verified = false;
        }

        if ($algo === 'hmac') {
            // Do HMAC verification
            $payload = $this->request->getRawBody();
            $computed = hash_hmac('sha256', $payload, $secret);
            $verified = hash_equals($computed, $providedSignature);
        } else {
            // Otherwise, fallback to static secret check
            $verified = hash_equals($secret, $providedSignature);
        }

        if(!$verified) {
            throw new HttpException('Invalid webhook signature', 401);
        }

        return $this;
    }

    protected function enforceEventIdempotency(?string $eventId)
    {
        $existing = WebhookEvent::query()
            ->where('provider', $this->provider)
            ->where('event_id', $eventId)
            ->exists();

        if ($existing) {
            throw new HttpException('Duplicate webhook event', 200);
        }
    }

    protected function storeNewWebhookEvent(?string $eventId): WebhookEvent
    {
        $event = new WebhookEvent;
        $event->provider = $this->provider;
        $event->event_id = $eventId;
        $event->payload = $this->request->input();
        $event->headers = $this->request->headers();
        $event->save();

        return $event;
    }
}
