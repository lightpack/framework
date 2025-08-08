<?php

namespace Lightpack\Console\Views\Config;

class WebhooksView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'webhooks' => [
        // Webhook settings
    ],
];
PHP;
    }
}
