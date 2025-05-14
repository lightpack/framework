<?php

namespace Lightpack\Webhook;

class WebhookController
{
    /**
     * Generic handler for all webhook providers.
     * Route: /webhook/{provider}
     */
    public function handle($provider)
    {
        $config = config('webhook');

        if (
            !isset($config[$provider]) ||
            !isset($config[$provider]['handler']) ||
            !class_exists($config[$provider]['handler'])
        ) {
            return response()
                ->setStatus(404)
                ->setBody('Unknown or unconfigured provider');
        }

        $handlerClass = $config[$provider]['handler'];
        $handler = new $handlerClass($config[$provider], $provider);

        return $handler->verifySignature()->handle();
    }
}
