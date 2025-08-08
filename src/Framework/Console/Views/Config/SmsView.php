<?php

namespace Lightpack\Console\Views\Config;

class SmsView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'sms' => [
        // SMS gateway settings
    ],
];
PHP;
    }
}
