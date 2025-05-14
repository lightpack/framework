<?php

namespace Lightpack\Webhook;

use Lightpack\Http\Request;
use Lightpack\Config\Config;
use Lightpack\Http\Response;

class WebhookController
{
    public function __construct(
        protected Config $config,
        protected Request $request,
        protected Response $response
    ) {}

    /**
     * Generic handler for all webhook providers.
     * Route: /webhook/{provider}
     */
    public function handle($provider)
    {
        $config = $this->config->get('webhook');

        if (
            !isset($config[$provider]) ||
            !isset($config[$provider]['handler']) ||
            !class_exists($config[$provider]['handler'])
        ) {
            return $this->response
                ->setStatus(404)
                ->setBody('Unknown or unconfigured provider');
        }

        $eventId = $this->request->input($config[$provider]['id']);
        $handlerClass = $config[$provider]['handler'];
        $handler = new $handlerClass($this->request, $config[$provider], $provider);
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
