<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;
use Lightpack\Deploy\HasDeployConfigTrait;

/**
 * Securely access phpMyAdmin via an SSH tunnel.
 *
 * phpMyAdmin is installed on the remote server and bound to 127.0.0.1 only.
 * This command opens a local SSH port-forward so you can access it in your
 * browser with zero public exposure.
 *
 * Usage:
 *   php console db:admin [env] [--port=8080]
 *   php console db:admin [env] --remove
 */
class DbAdminCommand extends Command
{
    use HasDeployConfigTrait;

    public function run(): int
    {
        $config = $this->loadConfig();

        if ($config === null) {
            return self::FAILURE;
        }

        $env       = $this->resolveEnvironment($config);
        $envConfig = $this->getEnvConfig($config, $env);

        if ($envConfig === null) {
            $this->printEnvironmentError($config, $env);
            return self::FAILURE;
        }

        $port = (int) ($this->args->get('port') ?? 8080);

        if ($port < 1024 || $port > 65535) {
            $this->output->error('Port must be between 1024 and 65535.');
            return self::FAILURE;
        }

        if ($this->args->has('remove')) {
            return $this->uninstall($envConfig, $env);
        }

        // Silently check whether the local port is already bound.
        // Suppress the warning with a temporary handler because Lightpack's
        // error handler converts E_WARNING to an exception.
        set_error_handler(static fn() => true);
        $sock = fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        restore_error_handler();

        if ($sock !== false) {
            fclose($sock);
            $this->output->error("Port {$port} is in use. Try --port=" . ($port + 1));
            return self::FAILURE;
        }

        $this->output->info("→ Setting up phpMyAdmin on {$env} ({$envConfig['host']})");
        $this->output->newline();

        $result = $this->executeRemote(
            $this->buildSshCommand($envConfig, $this->buildSetupScript($port, $env)),
            120
        );

        $this->output->newline();

        if (!$result['success']) {
            $this->output->error("Setup failed (exit code: {$result['exit_code']}).");
            return self::FAILURE;
        }

        $this->output->success('✓ phpMyAdmin is ready.');
        $this->output->newline();
        $this->output->info("→ Tunnel open — http://127.0.0.1:{$port}");
        $this->output->line('  Press Ctrl+C to close');
        $this->output->newline();

        $this->startTunnel($envConfig, $port);

        return self::SUCCESS;
    }

    private function uninstall(array $envConfig, string $env): int
    {
        $this->output->warning("→ Removing phpMyAdmin from {$env} ({$envConfig['host']})");
        $this->output->newline();

        $result = $this->executeRemote(
            $this->buildSshCommand($envConfig, $this->buildRemoveScript()),
            60
        );

        $this->output->newline();

        if (!$result['success']) {
            $this->output->error("Removal failed (exit code: {$result['exit_code']}).");
            return self::FAILURE;
        }

        $this->output->success("✓ phpMyAdmin removed from {$env}.");

        return self::SUCCESS;
    }

    /**
     * Builds the remote bash script that installs phpMyAdmin and configures
     * a localhost-only nginx vhost for it.
     *
     * Escaping layers in this method (read carefully before editing):
     *
     *   <<<BASH heredoc  — PHP processes \$ as literal $ (bash variable),
     *                      and {$port} as the PHP $port value.
     *
     *   <<'PMAEOF'       — single-quoted bash heredoc: bash writes everything
     *                      literally, so PHP's \$cfg → $cfg lands in the PHP
     *                      config file verbatim.
     *
     *   <<'NGEOF'        — single-quoted bash heredoc: bash writes nginx
     *                      variables ($uri, $document_root …) literally, so
     *                      nginx receives them unexpanded.
     *
     *   sed -e "s|…|…|"  — runs after the heredoc is written; bash expands
     *                      $PMA_ROOT and $PHP_FPM_SOCK inside the double-quoted
     *                      sed arguments, replacing the ALLCAPS placeholders.
     */
    private function buildSetupScript(int $port, string $env): string
    {
        return <<<BASH
set -e

# ── PHP-FPM socket ────────────────────────────────────────────────────────────
PHP_FPM_SOCK=\$(ls /run/php/php*-fpm.sock 2>/dev/null | sort -V | tail -1)
if [ -z "\$PHP_FPM_SOCK" ]; then
    echo "ERROR: no PHP-FPM socket found in /run/php/" >&2
    exit 1
fi

# ── phpMyAdmin path ───────────────────────────────────────────────────────────
if [ -d /usr/share/phpmyadmin ] && [ -f /usr/share/phpmyadmin/index.php ]; then
    PMA_ROOT="/usr/share/phpmyadmin"
elif [ -d /var/www/phpmyadmin ] && [ -f /var/www/phpmyadmin/index.php ]; then
    PMA_ROOT="/var/www/phpmyadmin"
else
    echo "Downloading phpMyAdmin..."
    mkdir -p /var/www/phpmyadmin
    curl -fsSL https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-english.tar.gz \
        | tar -xzf - --strip-components=1 -C /var/www/phpmyadmin
    chmod -R 755 /var/www/phpmyadmin
    PMA_ROOT="/var/www/phpmyadmin"
fi

# ── Writable tmp dirs (template cache + isolated sessions) ───────────────────
mkdir -p "\$PMA_ROOT/tmp/sessions"
chmod 777 "\$PMA_ROOT/tmp" "\$PMA_ROOT/tmp/sessions"

# ── config.inc.php ────────────────────────────────────────────────────────────
# Single-quoted heredoc <<'PMAEOF': bash writes content literally — PHP
# variables (\$cfg, \$i) are not expanded by bash and land verbatim in the file.
# The blowfish placeholder is injected afterwards by sed.
BLOWFISH=\$(openssl rand -base64 32 | tr -d '\n/+=' | head -c 32)
cat > "\$PMA_ROOT/config.inc.php" <<'PMAEOF'
<?php
\$cfg['blowfish_secret'] = '__BLOWFISH__';
\$i = 1;
\$cfg['Servers'][\$i]['auth_type']       = 'cookie';
\$cfg['Servers'][\$i]['host']            = 'localhost';
\$cfg['Servers'][\$i]['socket']          = '/var/run/mysqld/mysqld.sock';
\$cfg['Servers'][\$i]['connect_type']    = 'socket';
\$cfg['Servers'][\$i]['compress']        = false;
\$cfg['Servers'][\$i]['AllowNoPassword'] = false;
PMAEOF
sed -i "s|__BLOWFISH__|\$BLOWFISH|" "\$PMA_ROOT/config.inc.php"

# ── nginx vhost ───────────────────────────────────────────────────────────────
# Single-quoted heredoc <<'NGEOF': nginx variables (\$uri, \$document_root ...)
# are written literally. ALLCAPS tokens are substituted by sed below.
cat > /tmp/dbadmin-pma.conf <<'NGEOF'
server {
    listen      127.0.0.1:__PORT__;
    server_name _;
    root        __DOCROOT__;
    index       index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php$ {
        fastcgi_pass  unix:__SOCK__;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PHP_VALUE "session.save_path=__DOCROOT__/tmp/sessions\nsession.name=PMASESSION_{$env}_{$port}";
        include       fastcgi_params;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
NGEOF

sed -i \
    -e "s|__PORT__|{$port}|" \
    -e "s|__DOCROOT__|\$PMA_ROOT|" \
    -e "s|__SOCK__|\$PHP_FPM_SOCK|" \
    /tmp/dbadmin-pma.conf

cat /tmp/dbadmin-pma.conf | sudo lp-nginx-write "dbadmin-pma.conf"
rm -f /tmp/dbadmin-pma.conf

sudo lp-nginx-enable "dbadmin-pma.conf"
sudo systemctl reload nginx

# ── Reload PHP-FPM to clear opcache (opcache.validate_timestamps=0) ──────────
PHP_FPM_SERVICE=\$(basename "\$PHP_FPM_SOCK" .sock)
sudo systemctl reload "\$PHP_FPM_SERVICE"

echo "phpMyAdmin ready at http://127.0.0.1:{$port}"
BASH;
    }

    private function buildRemoveScript(): string
    {
        return <<<'BASH'
set -e

for conf in dbadmin-pma.conf dbadmin-local.conf; do
    if [ -f "/etc/nginx/sites-enabled/$conf" ]; then
        sudo lp-nginx-disable "$conf"
    fi
done

sudo systemctl reload nginx

if [ -d /var/www/phpmyadmin ]; then
    rm -rf /var/www/phpmyadmin
    echo "phpMyAdmin removed."
fi
BASH;
    }

    private function startTunnel(array $envConfig, int $port): void
    {
        $command = [
            'ssh', '-N',
            '-L', "{$port}:127.0.0.1:{$port}",
            '-i', $this->resolveKeyPath($envConfig['key']),
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ConnectTimeout=10',
            '-o', 'ServerAliveInterval=30',
            '-o', 'ServerAliveCountMax=3',
            'deploy@' . $envConfig['host'],
        ];

        passthru($this->escapeCommand($command));
    }

    private function escapeCommand(array $command): string
    {
        return implode(' ', array_map('escapeshellarg', $command));
    }
}
