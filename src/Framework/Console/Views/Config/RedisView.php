<?php

namespace Lightpack\Console\Views\Config;

class RedisView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'redis' => [
        // Redis connection settings
    ],
];
PHP;
    }
}
