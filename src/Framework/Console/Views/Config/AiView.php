<?php

namespace Lightpack\Console\Views\Config;

class AiView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'ai' => [
        // AI service configuration
    ],
];
PHP;
    }
}
