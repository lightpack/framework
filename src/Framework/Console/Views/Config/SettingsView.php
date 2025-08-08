<?php

namespace Lightpack\Console\Views\Config;

class SettingsView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'settings' => [
        // Application settings
    ],
];
PHP;
    }
}
