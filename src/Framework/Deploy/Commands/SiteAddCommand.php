<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;
use Lightpack\Deploy\HasDeployConfigTrait;

/**
 * Add an Nginx virtual host for a domain.
 *
 * Usage:
 *   php console server:site:add production --domain=example.com
 */
class SiteAddCommand extends Command
{
    use HasDeployConfigTrait;

    public function run()
    {
        $config = $this->loadConfig();

        if ($config === null) {
            return self::FAILURE;
        }

        $env = $this->resolveEnvironment($config);
        $envConfig = $this->getEnvConfig($config, $env);

        if ($envConfig === null) {
            $this->printEnvironmentError($config, $env);
            return self::FAILURE;
        }

        $domain = $this->args->get('domain');

        if (empty($domain)) {
            $this->output->newline();
            $this->output->info("→ Adding site on {$env} ({$envConfig['host']})");
            $this->output->newline();

            $domain = $this->ask('Domain');

            if (!$this->validateDomain($domain)) {
                $this->output->error("Invalid domain name: {$domain}");
                return self::FAILURE;
            }
        }

        if (!$this->validateDomain($domain)) {
            $this->output->error("Invalid domain name: {$domain}");
            return self::FAILURE;
        }
        $appPath    = $envConfig['path'];

        $this->output->info("→ Adding Nginx site for {$domain} ...");
        $this->output->newline();

        $remoteScript = $this->buildSiteScript($domain, $appPath);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 60);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("✓ Site {$domain} configured.");

            if (!filter_var($domain, FILTER_VALIDATE_IP)) {
                $this->output->info("→ Next: php console server:site:ssl {$env}");
            }

            return self::SUCCESS;
        }

        $this->output->error("Failed to add site (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

    /**
     * Prompt for a value, returning empty input as-is for validation.
     */

    private function buildSiteScript(string $domain, string $appPath): string
    {
        $configContent = <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$domain};
    root {$appPath}/public;
    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:PHP_FPM_SOCKET;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;
}
NGINX;

        return <<<BASH
set -e

# Detect the active PHP-FPM socket (avoids cli/fpm version mismatch)
PHP_FPM_SOCK=\$(ls /run/php/php*-fpm.sock 2>/dev/null | sort -V | tail -1)
if [ -z "\$PHP_FPM_SOCK" ]; then
    echo "ERROR: No PHP-FPM socket found in /run/php/" >&2
    exit 1
fi

# Patch the socket placeholder with the full detected socket path
NGINX_CONF=\$(cat << 'NGINX_EOF'
{$configContent}
NGINX_EOF
)
NGINX_CONF="\${NGINX_CONF/PHP_FPM_SOCKET/\$PHP_FPM_SOCK}"

echo "\$NGINX_CONF" | sudo lp-nginx-write "{$domain}.conf"
sudo lp-nginx-enable "{$domain}.conf"
sudo systemctl reload nginx

echo "Site {$domain} added and enabled."
BASH;
    }
}
