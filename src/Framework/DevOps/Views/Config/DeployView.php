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
 *   - server:site:remove (remove nginx virtual host)
 *   - server:site:ssl  (SSL certificate installation)
 *   - server:logs:view|tail (remote log access)
 *   - db:backup|restore (database operations)
 *   - server:schedule:setup  (cron job installation)
 *   - server:schedule:remove (remove cron job)
 *   - server:schedule:status (check cron job status)
 *   - server:env:pull   (download remote .env)
 *   - server:key:show   (display deploy SSH key for Git repo access)
 *   - server:queue:*    (queue daemon management)
 *   - server:config     (update PHP/Nginx runtime settings)
 *   - server:run        (run arbitrary commands on remote server)
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
            'host'    => '1.2.3.4',                       // Server IP or hostname
            'user'    => 'deploy',                        // Deploy user (created by server:provision)
            'key'     => '~/.ssh/id_rsa',                 // Your local SSH private key
            'repo'    => 'git@github.com:you/app.git',    // Git repo (SSH clone URL)
            //                                       ^
            // NOTE: If you change 'repo' after provisioning, copy the deploy
            // key from the server (server:key:show) and add it to the new repo's
            // deploy keys on GitHub/GitLab. The old repo key does NOT transfer.
            // GitHub requires removing the key from the OLD repo FIRST because
            // deploy keys are unique by fingerprint across your account.
            'timeout' => 300,                             // SSH command timeout in seconds

            // -----------------------------------------------------------------
            // Provisioning (used ONLY by server:provision)
            // -----------------------------------------------------------------
            // The initial SSH user for provisioning. On most cloud images this is
            // 'ubuntu', 'root', or similar — NOT the deploy user above.
            // The deploy user does not exist until after provisioning completes.
            'provision_user' => 'root',         // Initial SSH user for provisioning

            'php_version' => '8.3',             // PHP version: 8.1, 8.2, 8.3, 8.4
            'timezone'    => 'UTC',             // Server timezone
            'database'    => 'mysql',           // mysql | none
            'web_server'  => 'nginx',           // nginx only
            'git_host'    => 'github.com',      // Git host for SSH key scanning (github.com, gitlab.com, etc.)
            'ssl_email'   => 'you@example.com', // Email for SSL certificate renewal notices
            'db_name'     => 'lightpack',       // MySQL database name
            'db_user'     => 'lightpack',       // MySQL database user

            // -----------------------------------------------------------------
            // Application
            // -----------------------------------------------------------------
            'path'    => '/var/www/lightpack',  // App directory on the server
            'branch'  => 'main',                // Git branch to deploy

            // Post-deploy hooks: run after migrations, before PHP-FPM reload (optional)
            // Each hook runs as: cd /app/path && <hook command>
            // 'hooks' => [
            //     'php console cache:clear',
            //     'php console storage:link',
            // ],
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
