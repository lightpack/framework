<?php

namespace Lightpack\Console\Views\Config;

class LimitView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'limit' => [
        // Rate limiting settings
    ],
];
PHP;
    }
}
