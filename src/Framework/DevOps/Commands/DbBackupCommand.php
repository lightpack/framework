<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;
use Lightpack\Utils\Process;

/**
 * Backup the remote database and download it locally.
 *
 * Reads database credentials from the server's .env file,
 * runs mysqldump, and saves the SQL file locally.
 *
 * Usage:
 *   php console db:backup production
 *   php console db:backup --file=mybackup.sql
 */
class DbBackupCommand extends Command
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

        $appPath = $envConfig['path'];
        $timestamp = date('Y-m-d-His');
        $defaultFile = "backup-{$env}-{$timestamp}.sql";
        $localFile = $this->args->get('file') ?? $defaultFile;
        $localPath = DIR_ROOT . '/storage/backups/' . $localFile;

        // Ensure local backup directory exists
        $backupDir = dirname($localPath);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $this->output->info("Backing up database from {$env} ...");
        $this->output->newline();

        // Build remote mysqldump script
        $remoteScript = $this->buildDumpScript($appPath);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeBackup($sshCommand, $localPath);

        $this->output->newline();

        if ($result['success']) {
            $size = $this->formatBytes(filesize($localPath));
            $this->output->success("Backup saved: storage/backups/{$localFile} ({$size})");
            return self::SUCCESS;
        }

        $this->output->error("Backup failed (exit code: {$result['exit_code']}).");

        if (!empty($result['error'])) {
            $this->output->newline();
            $this->output->line('Error output:');
            $this->output->line($result['error']);
        }

        // Clean up empty/partial file
        if (file_exists($localPath) && filesize($localPath) === 0) {
            @unlink($localPath);
        }

        return self::FAILURE;
    }

    private function buildDumpScript(string $appPath): string
    {
        return <<<BASH
set -e

cd "{$appPath}"

# Read DB credentials from .env (only the ones we need)
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

# Use MYSQL_PWD env var (safer than -p on command line)
export MYSQL_PWD="\$DB_PASS"

mysqldump -h"\$DB_HOST" -u"\$DB_USER" --single-transaction --routines "\$DB_NAME" 2>/dev/null

# Clear password from env
unset MYSQL_PWD
BASH;
    }

    /**
     * Execute backup command, streaming stdout to a file and collecting stderr.
     *
     * @return array{success: bool, exit_code: int, error: string}
     */
    private function executeBackup(array $command, string $localPath): array
    {
        $process = new Process();
        $errorOutput = '';
        $fileHandle = fopen($localPath, 'w');

        if ($fileHandle === false) {
            return [
                'success' => false,
                'exit_code' => -1,
                'error' => "Cannot write to {$localPath}",
            ];
        }

        try {
            $process
                ->setTimeout(600)
                ->execute($command, function (string $line, string $type) use ($fileHandle, &$errorOutput) {
                    if ($type === 'stdout') {
                        fwrite($fileHandle, $line);
                    } else {
                        $errorOutput .= $line;
                    }
                });
        } catch (\Exception $e) {
            fclose($fileHandle);
            return [
                'success' => false,
                'exit_code' => -1,
                'error' => $e->getMessage(),
            ];
        }

        fclose($fileHandle);

        $exitCode = $process->getExitCode() ?? -1;

        return [
            'success' => $exitCode === 0 && filesize($localPath) > 0,
            'exit_code' => $exitCode,
            'error' => $errorOutput,
        ];
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
