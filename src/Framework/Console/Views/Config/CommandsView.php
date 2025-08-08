<?php

namespace Lightpack\Console\Views\Config;

class CommandsView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'commands' => [
        // Console commands registration
    ],
];
PHP;
    }
}
