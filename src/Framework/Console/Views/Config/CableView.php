<?php

namespace Lightpack\Console\Views\Config;

class CableView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'cable' => [
        // Real-time Cable settings
    ],
];
PHP;
    }
}
