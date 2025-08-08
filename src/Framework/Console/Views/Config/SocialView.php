<?php

namespace Lightpack\Console\Views\Config;

class SocialView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'social' => [
        // Social login and sharing settings
    ],
];
PHP;
    }
}
