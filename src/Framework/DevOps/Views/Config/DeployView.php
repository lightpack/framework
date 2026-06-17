<?php

namespace Lightpack\DevOps\Views\Config;

class DeployView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

/**
 * Lightpack DevOps deployment configuration.
 * 
 * IMPORTANT: If you change 'repo' after provisioning, copy the deploy key
 * from the server (server:key:show) and add it to the new repo's deploy keys.
 * GitHub requires removing the key from the OLD repo first because deploy
 * keys are unique by fingerprint across your account.
 */

return [
    'default' => 'production',

    'environments' => [
        'production' => [
            // Server identity
            'name' => 'lightpack',      // Label (SSH key comment, credentials file)
            'host' => '1.2.3.4',        // IP address of the server

            // SSH authentication
            'user' => 'deploy',         // Deploy user (created by provisioning)
            'key'  => '~/.ssh/id_rsa',  // Your local SSH private key

            // Git repository (SSH clone URL)
            'repo'    => 'git@github.com:you/app.git',
            'git_host' => 'github.com', // For SSH key scanning
            'branch'   => 'main',
            'timeout'  => 300,           // SSH command timeout (seconds)

            // -----------------------------------------------------------------
            // Provisioning (one-time, requires root SSH)
            // -----------------------------------------------------------------
            'provision_user' => 'root',  // Initial SSH user (ubuntu, root, etc.)
            'php_version'    => '8.3',   // 8.1 | 8.2 | 8.3 | 8.4
            'timezone'       => 'UTC',
            'database'       => 'mysql', // mysql | none
            'db_name'        => 'lightpack',
            'db_user'        => 'lightpack',
            'ssl_email'      => 'you@example.com', // Certbot renewal notices

            // -----------------------------------------------------------------
            // Application paths
            // -----------------------------------------------------------------
            'path' => '/var/www/lightpack',

            // Optional: run after migrations, before PHP-FPM reload
            // 'hooks' => [
            //     'php console cache:clear',
            //     'php console storage:link',
            // ],
        ],
    ],
];
PHP;
    }
}
