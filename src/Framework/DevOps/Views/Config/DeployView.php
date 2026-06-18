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
 *
 * Each entry under 'deploy' is an environment (production, staging, etc.).
 * Commands default to 'production' when no environment argument is given.
 *
 * After provisioning, add the server's deploy key to your Git repository:
 *   php console server:key:show
 *   GitHub → Settings → Deploy keys → Add deploy key
 *
 * If you ever change 'repo' on an already-provisioned server, remove the old
 * key from the previous repository first — GitHub does not allow the same
 * fingerprint on two repositories under the same account.
 */

return [
    'deploy' => [
        'production' => [
            'host'   => '1.2.3.4',                        // Server IP or hostname
            'key'    => '~/.ssh/id_rsa',                   // Local SSH private key
            'repo'   => 'git@github.com:you/app.git',      // Git SSH clone URL
            'branch' => 'main',
            'path'   => '/var/www/myapp',                  // Absolute path on server

            // Optional: run after migrations, before PHP-FPM reload
            // 'hooks' => [
            //     'php console cache:clear',
            //     'php console storage:link',
            // ],
        ],

        // 'staging' => [
        //     'host'   => '1.2.3.4',
        //     'key'    => '~/.ssh/id_rsa',
        //     'repo'   => 'git@github.com:you/app.git',
        //     'branch' => 'develop',
        //     'path'   => '/var/www/myapp-staging',
        // ],
    ],
];
PHP;
    }
}
