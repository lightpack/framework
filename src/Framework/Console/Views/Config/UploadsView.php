<?php

namespace Lightpack\Console\Views\Config;

class UploadsView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'uploads' => [
        // Uploads settings
    ],
];
PHP;
    }
}
