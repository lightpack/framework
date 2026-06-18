<?php

namespace Lightpack\DevOps\Views\Config;

class DeployView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

/**
 * Lightpack deployment configuration.
 */

return [
    'deploy' => [
        'production' => [
            'host'   => '1.2.3.4',
            'key'    => '~/.ssh/id_rsa',
            'repo'   => 'git@github.com:you/app.git',
            'branch' => 'main',
            'path'   => '/var/www/lightpack',
            'hooks' => [
                // ...
            ],
        ],
    ],
];
PHP;
    }
}
