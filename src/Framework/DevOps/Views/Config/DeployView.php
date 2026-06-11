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
 * This file is used by:
 *   - server:provision  (one-time server setup, requires root or sudo)
 *   - app:deploy        (daily code deployments, uses deploy user)
 *   - app:rollback      (revert to previous commit)
 *   - server:site:add   (nginx virtual host management)
 *   - server:ssl        (SSL certificate installation)
 *   - logs:view|tail    (remote log access)
 *   - db:backup|restore (database operations)
 *   - schedule:setup    (cron job installation)
 *   - env:pull          (download remote .env)
 *   - server:queue:*    (queue daemon management)
 *
 * Copy this file to config/deploy.php and update the values for your server.
 */

return [
    'default' => 'production',

    'environments' => [
        'production' => [
            // -----------------------------------------------------------------
            // SSH Connection
            // -----------------------------------------------------------------
            'host'    => '1.2.3.4',                        // Server IP or hostname
            'user'    => 'deploy',                       // User that will be CREATED by provision
            'key'     => '~/.ssh/id_rsa',               // Your local SSH private key
            'repo'    => 'git@github.com:you/app.git',  // Git repo (for first deploy clone)
            'timeout' => 300,                            // Seconds for deploy commands

            // -----------------------------------------------------------------
            // Provisioning (used ONLY by server:provision)
            // -----------------------------------------------------------------
            // The initial SSH user for provisioning. On cloud images this is
            // often 'ubuntu', 'kubuntu', or 'root' — NOT the deploy user above.
            // The deploy user does not exist until after provisioning completes.
            'provision_user' => 'root',      // Initial SSH user (root/ubuntu/kubuntu)

            'php_version' => '8.3',          // PHP version to install: 8.1, 8.2, 8.3, 8.4
            'timezone'    => 'UTC',          // Server timezone
            'database'    => 'mysql',        // mysql | none
            'web_server'  => 'nginx',        // nginx only for now
            'ssl_email'   => 'you@example.com', // Email for SSL certificate renewal notices
            'db_name'     => 'lightpack',    // Database name
            'db_user'     => 'lightpack',    // Database user

            // -----------------------------------------------------------------
            // Application
            // -----------------------------------------------------------------
            'path'    => '/var/www/lightpack', // App path on the server
            'branch'  => 'main',             // Git branch to deploy
        ],

        // 'staging' => [
        //     'host'    => 'staging.yourdomain.com',
        //     'user'    => 'deploy',
        //     'key'     => '~/.ssh/id_rsa',
        //     'path'    => '/var/www/staging',
        //     'branch'  => 'develop',
        //     'timeout' => 300,
        // ],
    ],
];
PHP;
    }
}
