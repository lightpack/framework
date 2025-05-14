<?php

namespace Lightpack\Webhook;

use Lightpack\Exceptions\HttpException;
use Lightpack\Http\Response;

abstract class BaseWebhookHandler
{
    /**
     * @param array $config   Webhook provider configuration (secret, headers, etc).
     * @param string $provider The provider key (e.g., 'stripe', 'github').
     */
    public function __construct(
        protected array $config,
        protected string $provider
    ) {}

    /**
     * Handle the webhook event.
     *
     * This method must be implemented by subclasses to process the webhook payload
     * and return an HTTP response.
     *
     * @return Response HTTP response to be sent to the webhook provider
     */
    abstract public function handle(): Response;

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
        $providedSignature = request()->header($header);
        $secret = $this->config['secret'] ?? null;
        $algo = $this->config['algo'] ?? null;

        if (!$secret) {
            $verified = false;
        }

        if ($algo === 'hmac') {
            // Do HMAC verification
            $payload = request()->getRawBody();
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
}
