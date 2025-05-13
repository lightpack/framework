<?php
namespace Lightpack\Webhook;

use Lightpack\Http\Request;

class WebhookService
{
    /**
     * Verifies the webhook signature using HMAC or static secret.
     *
     * @param Request $request
     * @param string $provider (e.g., 'stripe', 'github')
     * @return bool|string  True if valid, error string if invalid
     */
    public function verifySignature(Request $request, string $provider)
    {
        $config = config('webhook');
        $settings = $config[$provider] ?? [];
        $secret = $settings['secret'] ?? null;
        $header = $settings['signature_header'] ?? 'X-Signature';
        $algo = $settings['algo'] ?? null; // 'hmac' or 'static'

        if (!$secret) {
            return 'No secret configured';
        }

        $provided = $request->header($header);
        if ($algo === 'hmac') {
            $payload = json_encode($request->input());
            $computed = crypto()->hash('sha256', $payload, $secret);
            if (!hash_equals($computed, $provided)) {
                return 'Invalid HMAC signature';
            }
        } else { // static secret
            if (!hash_equals($secret, $provided)) {
                return 'Invalid static secret';
            }
        }
        return true;
    }

    public function process(Request $request, string $provider = 'generic'): array
    {
        // Signature verification (if configured)
        $verify = $this->verifySignature($request, $provider);
        if ($verify !== true) {
            return ['success' => false, 'error' => $verify];
        }

        $data = $request->input();
        // Optionally: log event
        if ($data) {
            // Process event (custom logic here)
            return ['success' => true, 'data' => $data];
        }
        return ['success' => false, 'error' => 'Invalid payload'];
    }
}
