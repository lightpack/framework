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

        $eventId = request()->input($config['provider']['id']);
        $handlerClass = $config[$provider]['handler'];
        $handler = new $handlerClass($config[$provider], $provider);
        $handler->verifySignature();
        $webhookEvent = $handler->storeEvent($eventId);

        try {
            $response = $handler->handle();
            $webhookEvent->status = 'processed';
            $webhookEvent->save();
        } catch (\Throwable $e) {
            $webhookEvent->status = 'failed';
            $webhookEvent->save();
            throw $e;
        }

        return $response;
    }
}
