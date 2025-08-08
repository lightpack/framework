<?php

namespace Lightpack\Console\Views\Config;

class EventsView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'events' => [
        // Event system settings
    ],
];
PHP;
    }
}
