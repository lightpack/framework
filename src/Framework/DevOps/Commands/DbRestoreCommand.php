<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Restore a database backup to the remote server.
 *
 * Uploads a local SQL file to the server and imports it.
 * WARNING: This is destructive — it will overwrite existing data.
 *
 * Usage:
 *   php lightpack db:restore production --file=backup-2026-01-15.sql
 */
class DbRestoreCommand extends Command
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

        $file = $this->args->get('file');

        if (empty($file)) {
            $this->output->error('Backup file is required. Use --file=backup.sql');
            return self::FAILURE;
        }

        // Prevent path traversal in file names
        if (strpbrk($file, '/\\..') !== false) {
            $this->output->error('Invalid file name. Path traversal is not allowed.');
            return self::FAILURE;
        }

        $localPath = DIR_ROOT . '/storage/backups/' . $file;

        if (!file_exists($localPath)) {
            $this->output->error("Backup file not found: storage/backups/{$file}");
            return self::FAILURE;
        }

        $appPath = $envConfig['path'];
        $remoteTemp = "/tmp/restore-{$file}";

        // Security confirmation
        $this->output->warning('DATABASE RESTORE');
        $this->output->newline();
        $this->output->line("Environment: {$env}");
        $this->output->line("File:        {$file}");
        $this->output->line("Size:        " . $this->formatBytes(filesize($localPath)));
        $this->output->newline();
        $this->output->warning('This will DESTROY existing data. Are you sure? (yes/no)');
        $this->output->newline();

        $response = trim(fgets(STDIN));

        if (strtolower($response) !== 'yes') {
            $this->output->line('Restore cancelled.');
            return self::FAILURE;
        }

        $this->output->newline();
        $this->output->info("Uploading backup to {$env} ...");
        $this->output->newline();

        // Step 1: Upload SQL file via SCP
        $scpResult = $this->uploadFile($envConfig, $localPath, $remoteTemp);

        if (!$scpResult['success']) {
            $this->output->error("Failed to upload backup (exit code: {$scpResult['exit_code']}).");
            return self::FAILURE;
        }

        // Step 2: Run restore script on server
        $this->output->info('Restoring database ...');
        $this->output->newline();

        $remoteScript = $this->buildRestoreScript($appPath, $remoteTemp);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);
        $result = $this->executeRemote($sshCommand, 300);

        // Step 3: Clean up remote temp file regardless of success
        $this->executeRemote(
            $this->buildSshCommand($envConfig, "rm -f {$remoteTemp}"),
            10
        );

        $this->output->newline();

        if ($result['success']) {
            $this->output->success('Database restored.');
            return self::SUCCESS;
        }

        $this->output->error("Restore failed (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

    private function uploadFile(array $envConfig, string $localPath, string $remotePath): array
    {
        $user = $envConfig['user'];
        $host = $envConfig['host'];
        $key = $this->resolveKeyPath($envConfig['key'] ?? '~/.ssh/id_rsa');

        $scpCommand = [
            'scp',
            '-i', $key,
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ConnectTimeout=10',
            $localPath,
            "{$user}@{$host}:{$remotePath}",
        ];

        return $this->executeRemote($scpCommand, 120);
    }

    private function buildRestoreScript(string $appPath, string $remoteSqlPath): string
    {
        return <<<BASH
set -e

cd "{$appPath}"

# Read DB credentials from .env
if [ ! -f .env ]; then
    echo "ERROR: .env file not found on server" >&2
    exit 1
fi

DB_HOST=$(grep '^DB_HOST=' .env | cut -d'=' -f2- | tr -d '"' | tr -d "'")
DB_NAME=$(grep '^DB_NAME=' .env | cut -d'=' -f2- | tr -d '"' | tr -d "'")
DB_USER=$(grep '^DB_USER=' .env | cut -d'=' -f2- | tr -d '"' | tr -d "'")
DB_PASS=$(grep '^DB_PASS=' .env | cut -d'=' -f2- | tr -d '"' | tr -d "'")

if [ -z "\$DB_NAME" ] || [ -z "\$DB_USER" ]; then
    echo "ERROR: DB_NAME or DB_USER not found in .env" >&2
    exit 1
fi

# Use MYSQL_PWD env var for security
export MYSQL_PWD="\$DB_PASS"

mysql -h"\$DB_HOST" -u"\$DB_USER" "\$DB_NAME" < "{$remoteSqlPath}"

unset MYSQL_PWD

echo "Database restored successfully."
BASH;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
