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
        // Enable or disable caching for settings
        'cache' => true,
    
        // Cache TTL (time to live) in seconds (e.g., 3600 = 1 hour)
        'ttl' => 3600,
    ],
];
PHP;
    }
}
