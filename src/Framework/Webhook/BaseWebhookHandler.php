<?php

namespace Lightpack\Webhook;

use Lightpack\Http\Response;

abstract class BaseWebhookHandler
{
    public function __construct(
        protected array $config,
        protected string $provider
    ) {}

    /**
     * Handle the webhook event (to be implemented by subclasses)
     */
    abstract public function handle(): Response;

    /**
     * Optionally override for provider-specific signature verification
     */
    public function verifySignature()
    {
        $header = $this->config['signature_header'] ?? 'X-Webhook-Signature';
        $providedSignature = request()->header($header);
        $secret = $this->config['secret'] ?? null;
        $algo = $this->config['algo'] ?? null;

        if (!$secret) {
            return false;
        }

        if ($algo === 'hmac') {
            // Do HMAC verification
            $payload = request()->getRawBody();
            $computed = hash_hmac('sha256', $payload, $secret);
            return hash_equals($computed, $providedSignature);
        }

        // Otherwise, fallback to static secret check
        return hash_equals($secret, $providedSignature);
    }
}
