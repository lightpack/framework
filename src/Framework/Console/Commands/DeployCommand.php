<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Deploy\Deployer;

/**
 * Deploy the application to a remote server via SSH.
 *
 * Usage:
 *   php lightpack app:deploy              Deploy to default environment
 *   php lightpack app:deploy --env=staging  Deploy to specific environment
 */
class DeployCommand extends Command
{
    public function run()
    {
        $configPath = DIR_ROOT . '/config/deploy.php';

        if (!file_exists($configPath)) {
            $this->output->error('Deploy config not found.');
            $this->output->newline();
            $this->output->line('Create config/deploy.php with your server settings:');
            $this->output->newline();
            $this->printConfigExample();
            $this->output->newline();
            return self::FAILURE;
        }

        $config = require $configPath;
        $defaultEnv = $config['default'] ?? 'production';
        $env = $this->args->get('env', $defaultEnv);

        if (!isset($config['environments'][$env])) {
            $this->output->error("Environment '{$env}' not found in config/deploy.php.");
            $this->output->newline();
            $this->output->line('Available environments:');

            $deployer = new Deployer($config);
            foreach ($deployer->getEnvironments() as $name) {
                $this->output->line("  - {$name}");
            }

            $this->output->newline();
            return self::FAILURE;
        }

        $envConfig = $config['environments'][$env];

        $this->output->info("Deploying to {$env} ({$envConfig['host']}) ...");
        $this->output->newline();

        $deployer = new Deployer($config);

        try {
            $result = $deployer->deploy($env);
        } catch (\RuntimeException $e) {
            $this->output->error($e->getMessage());
            $this->output->newline();
            return self::FAILURE;
        }

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Deployed successfully to {$env}.");
            return self::SUCCESS;
        }

        $this->output->error("Deploy failed (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

    private function printConfigExample(): void
    {
        $example = <<<'PHP'
<?php

return [
    'default' => 'production',

    'environments' => [
        'production' => [
            'host' => '1.2.3.4',
            'user' => 'deploy',
            'key' => '~/.ssh/id_rsa',
            'path' => '/var/www/myapp',
            'branch' => 'main',
        ],
        'staging' => [
            'host' => '5.6.7.8',
            'user' => 'deploy',
            'key' => '~/.ssh/id_rsa_staging',
            'path' => '/var/www/staging',
            'branch' => 'develop',
        ],
    ],
];
PHP;

        echo $example . PHP_EOL;
    }
}
