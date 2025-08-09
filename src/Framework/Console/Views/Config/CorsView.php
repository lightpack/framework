<?php

namespace Lightpack\Console\Views\Config;

class CorsView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'cors' => [
        'headers' => [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Authorization',
            'Access-Control-Max-Age' => 2 * 60,
        ]
    ],
];
PHP;
    }
}
