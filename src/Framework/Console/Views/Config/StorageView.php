<?php

namespace Lightpack\Console\Views\Config;

class StorageView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'storage' => [
        // File storage settings
    ],
];
PHP;
    }
}
