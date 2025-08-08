<?php

namespace Lightpack\Console\Views\Config;

class MfaView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'mfa' => [
        // Multi-factor authentication settings
    ],
];
PHP;
    }
}
