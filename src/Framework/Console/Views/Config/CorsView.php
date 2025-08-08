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
        // CORS settings
    ],
];
PHP;
    }
}
