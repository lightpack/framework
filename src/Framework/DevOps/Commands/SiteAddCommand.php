<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;
use Lightpack\DevOps\Deployer;

/**
 * Add an Nginx virtual host for a domain.
 *
 * Usage:
 *   php lightpack server:site:add production --domain=example.com
 *   php lightpack server:site:add --domain=example.com --www   # include www alias
 */
class SiteAddCommand extends Command
{
    use HasDeployConfig;

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
            $this->output->error('Domain is required. Use --domain=example.com');
            return self::FAILURE;
        }

        if (!$this->validateDomain($domain)) {
            $this->output->error("Invalid domain name: {$domain}");
            return self::FAILURE;
        }

        $includeWww = $this->args->has('www');
        $appPath = $envConfig['path'];
        $phpVersion = $envConfig['php_version'] ?? '8.3';

        $this->output->info("Adding Nginx site for {$domain} ...");
        $this->output->newline();

        $remoteScript = $this->buildSiteScript($domain, $appPath, $phpVersion, $includeWww);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 60);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Site {$domain} configured.");
            $this->output->line("Next: php lightpack server:ssl {$env} --domain={$domain}");
            return self::SUCCESS;
        }

        $this->output->error("Failed to add site (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

    private function buildSiteScript(string $domain, string $appPath, string $phpVersion, bool $includeWww): string
    {
        $serverNames = $includeWww ? "{$domain} www.{$domain}" : $domain;
        $fpmSocket = "/run/php/php{$phpVersion}-fpm.sock";

        $configContent = <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$serverNames};
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
        fastcgi_pass unix:{$fpmSocket};
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

        // Escape for bash heredoc - only escape $ characters that are NOT bash variables
        // We use 'EOF' (quoted) so no variable expansion happens inside the heredoc
        $escapedConfig = str_replace("\\", "\\\\", $configContent);

        return <<<BASH
domain="{$domain}"
config_path="/etc/nginx/sites-available/\${domain}.conf"
enabled_path="/etc/nginx/sites-enabled/\${domain}.conf"

cat << 'NGINX_EOF' | sudo tee "\$config_path" >/dev/null
{$configContent}
NGINX_EOF

sudo ln -sf "\$config_path" "\$enabled_path"
sudo systemctl reload nginx

echo "Site \$domain added and enabled."
BASH;
    }
}
