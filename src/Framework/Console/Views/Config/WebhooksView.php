<?php

namespace Lightpack\Console\Views\Config;

class WebhooksView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'my_webhook' => [
        'secret' => 'webhook-secret',
        'algo' => 'hmac', // or 'static'
        'id' => 'id', // payload event ID
        'handler' => App\Webhooks\MyWebhookHandler::class,
    ],
];
PHP;
    }
}
