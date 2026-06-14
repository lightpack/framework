<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Display the deploy user's public SSH key for Git authentication.
 *
 * When you change 'repo' in deploy.php to a new Git host, add this key
 * to the new repository's deploy keys (Settings → Deploy keys).
 *
 * Usage:
 *   php console server:key:show production
 */
class ServerKeyShowCommand extends Command
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

        $deployUser = $envConfig['user'] ?? 'deploy';

        $this->output->info("Fetching public SSH key for {$env} ({$deployUser}) ...");
        $this->output->newline();

        $remoteScript = <<<BASH
set -e

for key in /home/{$deployUser}/.ssh/id_ed25519.pub /home/{$deployUser}/.ssh/id_rsa.pub; do
    if [ -f "\$key" ]; then
        cat "\$key"
        exit 0
    fi
done

echo "No deploy SSH key found."
echo "Generate one first: php console server:provision {$env}"
exit 1
BASH;

        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);
        $result = $this->executeRemote($sshCommand, 10);

        $this->output->newline();

        if (!$result['success']) {
            $this->output->error('Failed to retrieve SSH key.');
            return self::FAILURE;
        }

        $key = trim($result['output']);

        if (str_starts_with($key, 'ssh-')) {
            $this->output->success('Deploy SSH key:');
            $this->output->newline();
            $this->output->line($key);
            $this->output->newline();
            $this->output->info('Add this key to your Git repository:');
            $this->output->line('  GitHub → Settings → Deploy keys → Add deploy key');
            $this->output->newline();
            $this->output->warning('If you changed "repo" in deploy.php:');
            $this->output->line('  1. REMOVE the key from the OLD repo first');
            $this->output->line('  2. ADD the key to the NEW repo');
            $this->output->newline();
            $this->output->line('Note: GitHub does not allow the same deploy key');
            $this->output->line('on two repositories under the same account.');
        } else {
            $this->output->error('No SSH key found on the server.');
        }

        return self::SUCCESS;
    }
}
