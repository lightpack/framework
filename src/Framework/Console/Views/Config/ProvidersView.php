<?php

namespace Lightpack\Console\Views\Config;

class ProvidersView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'providers' => [
        // Service provider registration
    ],
];
PHP;
    }
}
