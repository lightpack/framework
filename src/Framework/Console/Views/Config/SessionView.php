<?php

namespace Lightpack\Console\Views\Config;

class SessionView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'session' => [
        // Session settings
    ],
];
PHP;
    }
}
