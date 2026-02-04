<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Console\Views\MigrationView;
use Lightpack\Console\Output;

class CreateMigration implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $output = new Output();
        $schemas = self::getPredefinedSchemas();
        $support = $this->parseSupportArgument($arguments);

        if ($support === '') {
            $this->showError($output, "You must provide a value for --support. Example: --support=users", null, array_keys($schemas));
            return;
        }

        if ($support) {
            if (!isset($schemas[$support])) {
                $this->showError($output, "Unknown support schema: \"{$support}\".", null, array_keys($schemas));
                return;
            }
            [$filepath, $template] = $this->buildMigrationFile("{$support}_schema", $schemas[$support]);
        } else {
            $migration = $arguments[0] ?? null;
            if (!$migration) {
                $this->showError(
                    $output,
                    "Please provide a migration file name.",
                    "You can use --support=<schema> for a predefined migration.",
                    array_keys($schemas)
                );
                return;
            }
            if (!preg_match('/^[\w_]+$/', $migration)) {
                $this->showError($output, "Migration file name can only contain alphanumeric characters and underscores.");
                return;
            }
            [$filepath, $template] = $this->buildMigrationFile($migration);
        }

        file_put_contents($filepath, $template);
        $output->newline();
        $output->success("✓ Migration created in {$filepath}");
        $output->newline();
    }

    /**
     * Outputs error/info blocks with optional tip and supported schemas.
     */
    protected function showError(Output $output, string $error, ?string $tip = null, ?array $schemas = null): void
    {
        $output->newline();
        $output->error($error);
        $output->newline();

        if ($tip) {
            $output->newline();
            $output->info('[Tip]:');
            $output->line($tip);
        }
        if ($schemas) {
            $output->newline();
            $output->info("[Supported]:");
            $output->line(implode(', ', $schemas));
        }
    }

    /**
     * Returns migration file path and template for given name and optional template class.
     */
    protected function buildMigrationFile(string $name, ?string $templateClass = null): array
    {
        $timestamp = date('YmdHis');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = "./database/migrations/{$filename}";
        $template = $templateClass ? $templateClass::getTemplate() : MigrationView::getTemplate();
        return [$filepath, $template];
    }


    /**
     * Parses the --support argument. Returns string value, or empty string if --support= is present with no value, or null if not present.
     */
    protected function parseSupportArgument(array $arguments): ?string
    {
        foreach ($arguments as $arg) {
            if ($arg === '--support' || $arg === '--support=') {
                return '';
            }
            if (strpos($arg, '--support=') === 0) {
                return substr($arg, strlen('--support='));
            }
        }
        return null;
    }

    protected function handleSupportSchema($support, $schemas, $output): ?array
    {
        if (isset($schemas[$support])) {
            $migration = date('YmdHis') . '_' . $support . '_schema';
            $migrationFilepath = './database/migrations/' . $migration . '.php';
            $template = $schemas[$support]::getTemplate();
            return [$migrationFilepath, $template];
        } else {
            $output->newline();
            $output->errorLabel();
            $output->error("Unknown support schema: \"{$support}\".");
            $output->newline();
            $output->infoLabel();
            $output->info("Supported values are: " . implode(', ', array_keys($schemas)) . ".");
            return null;
        }
    }

    protected function handleClassicMigration($arguments, $schemas, $output): ?array
    {
        $migration = $arguments[0] ?? null;
        if (null === $migration) {
            $output->newline();
            $output->errorLabel();
            $output->error("Please provide a migration file name.");
            $output->newline();
            $output->infoLabel(' Tip ');
            $output->info("You can use --support=<schema> for a predefined migration.");
            $output->info("Supported: " . implode(', ', array_keys($schemas)) . ".");
            return null;
        }
        if (!preg_match('/^[\w_]+$/', $migration)) {
            $output->newline();
            $output->errorLabel();
            $output->newline();
            $output->error("Migration file name can only contain alphanumeric characters and underscores.");
            return null;
        }
        $migration = date('YmdHis') . '_' . $migration;
        $migrationFilepath = './database/migrations/' . $migration . '.php';
        $template = MigrationView::getTemplate();
        return [$migrationFilepath, $template];
    }

    /**
     * Returns an array of predefined schema view classes.
     * Key: support name, Value: fully qualified class name
     */
    protected static function getPredefinedSchemas(): array
    {
        return [
            'jobs' => \Lightpack\Console\Views\Migrations\JobsView::class,
            'rbac' => \Lightpack\Console\Views\Migrations\RbacView::class,
            'tags' => \Lightpack\Console\Views\Migrations\TagsView::class,
            'users' => \Lightpack\Console\Views\Migrations\UsersView::class,
            'cache' => \Lightpack\Console\Views\Migrations\CacheView::class,
            'cable' => \Lightpack\Console\Views\Migrations\CableView::class,
            'audits' => \Lightpack\Console\Views\Migrations\AuditsView::class,
            'social' => \Lightpack\Console\Views\Migrations\SocialView::class,
            'uploads' => \Lightpack\Console\Views\Migrations\UploadsView::class,
            'secrets' => \Lightpack\Console\Views\Migrations\SecretsView::class,
            'settings' => \Lightpack\Console\Views\Migrations\SettingsView::class,
            'webhooks' => \Lightpack\Console\Views\Migrations\WebhooksView::class,
            'taxonomies' => \Lightpack\Console\Views\Migrations\TaxonomiesView::class,
        ];
    }
}
