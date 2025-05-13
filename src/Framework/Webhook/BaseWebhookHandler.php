<?php
namespace Lightpack\Webhook;

use Lightpack\Http\Request;
use Lightpack\Http\Response;

abstract class BaseWebhookHandler
{
    protected $config;
    protected $provider;

    public function __construct(array $config, string $provider)
    {
        $this->config = $config;
        $this->provider = $provider;
    }

    /**
     * Optionally override for provider-specific signature verification
     */
    public function verifySignature(Request $request)
    {
        // Default: use generic verification from config
        $secret = $this->config['secret'] ?? null;
        $header = $this->config['signature_header'] ?? 'X-Signature';
        $algo = $this->config['algo'] ?? null;
        $provided = $request->header($header);
        if (!$secret) {
            return false;
        }
        if ($algo === 'hmac') {
            $payload = json_encode($request->input());
            $computed = hash_hmac('sha256', $payload, $secret);
            return hash_equals($computed, $provided);
        } else {
            return hash_equals($secret, $provided);
        }
    }

    /**
     * Handle the webhook event (to be implemented by subclasses)
     */
    abstract public function handle(): Response;
}
