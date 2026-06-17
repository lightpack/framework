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
 * IMPORTANT: If you change 'app' => 'repo' after provisioning, copy the deploy key
 * from the server (server:key:show) and add it to the new repo's deploy keys.
 * GitHub requires removing the key from the OLD repo first because deploy
 * keys are unique by fingerprint across your account.
 */

return [
    'default' => 'production',

    'environments' => [
        'production' => [

            // -----------------------------------------------------------------
            // SSH — used by all commands
            // -----------------------------------------------------------------
            'host'    => '1.2.3.4',       // IP address of the server
            'user'    => 'deploy',         // Deploy user (created by provisioning)
            'key'     => '~/.ssh/id_rsa',  // Your local SSH private key
            'timeout' => 300,              // SSH command timeout (seconds)
            'php'     => '8.3',            // PHP version: 8.1 | 8.2 | 8.3 | 8.4

            // -----------------------------------------------------------------
            // Provision — one-time server setup (server:provision only)
            // -----------------------------------------------------------------
            'provision' => [
                'user'     => 'root',          // Initial SSH user (ubuntu, root, etc.)
                'name'     => 'lightpack',     // Server label (SSH key comment)
                'timezone' => 'UTC',
                'database' => 'mysql',         // mysql | none
                'db_name'  => 'lightpack',
                'db_user'  => 'lightpack',
                'git_host' => 'github.com',    // For SSH key scanning
            ],

            // -----------------------------------------------------------------
            // App — deployment and ongoing maintenance
            // -----------------------------------------------------------------
            'app' => [
                'repo'      => 'git@github.com:you/app.git', // Git SSH clone URL
                'branch'    => 'main',
                'path'      => '/var/www/lightpack',
                'ssl_email' => 'you@example.com', // Certbot renewal notices

                // Optional: run after migrations, before PHP-FPM reload
                // 'hooks' => [
                //     'php console cache:clear',
                //     'php console storage:link',
                // ],
            ],
        ],
    ],
];
PHP;
    }
}
