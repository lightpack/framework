<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;
use Lightpack\DevOps\Deployer;

/**
 * Deploy the application to a remote server via SSH.
 *
 * Usage:
 *   php console app:deploy              Deploy to default environment
 *   php console app:deploy production   Deploy to a specific environment
 *   php console app:deploy staging      Deploy to staging environment
 */
class DeployCommand extends Command
{
    use HasDeployConfig;

    public function run()
    {
        $config = $this->loadConfig();

        if ($config === null) {
            return self::FAILURE;
        }

        $env = $this->resolveEnvironment($config);

        if (!isset($config['environments'][$env])) {
            $this->printEnvironmentError($config, $env);
            return self::FAILURE;
        }

        $envConfig = $config['environments'][$env];
        $deployer = new Deployer($config);

        $this->output->info("Deploying to {$env} ({$envConfig['host']}) ...");
        $this->output->newline();

        return $this->deployCode($deployer, $env);
    }

    private function loadConfig(): ?array
    {
        $configPath = DIR_ROOT . '/config/deploy.php';

        if (!file_exists($configPath)) {
            $this->output->error('Deploy config not found.');
            $this->output->newline();
            $this->output->line('Create config/deploy.php with your server settings:');
            $this->output->newline();
            $this->printConfigExample();
            $this->output->newline();
            return null;
        }

        return require $configPath;
    }

    private function deployCode(Deployer $deployer, string $env): int
    {
        $this->output->line('Deploying code ...');
        $this->output->newline();

        $localEnvPath = DIR_ROOT . '/.env.' . $env;

        try {
            $result = $deployer->deploy($env, file_exists($localEnvPath) ? $localEnvPath : null);
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
            'host'    => '1.2.3.4',
            'user'    => 'deploy',
            'key'     => '~/.ssh/id_rsa',
            'path'    => '/var/www/myapp',
            'branch'  => 'main',
            'timeout' => 300,

            // Post-deploy hooks: run after migrations, before FPM reload (optional)
            // 'hooks' => [
            //     'php console cache:clear',
            //     'php console storage:link',
            // ],
        ],
        'staging' => [
            'host'   => '5.6.7.8',
            'user'   => 'deploy',
            'key'    => '~/.ssh/id_rsa',
            'path'   => '/var/www/staging',
            'branch' => 'develop',
        ],
    ],
];
PHP;

        echo $example . PHP_EOL;
        $this->output->newline();
        $this->output->line('Env files:');
        $this->output->line('  .env.production  -> copied to server as .env');
        $this->output->line('  .env.staging     -> copied to server as .env');
        $this->output->newline();
    }
}
