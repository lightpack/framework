<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\MigrationView;
use Lightpack\Console\Output;

class CreateMigration implements ICommand
{
    public function run(array $arguments = [])
    {
        $output = new Output();
        $support = $this->parseSupportArgument($arguments);
        $schemas = self::getPredefinedSchemas();

        if ($support) {
            $result = $this->handleSupportSchema($support, $schemas, $output);
        } else {
            $result = $this->handleClassicMigration($arguments, $schemas, $output);
        }

        if ($result === null) {
            return;
        }

        [$migrationFilepath, $template] = $result;
        file_put_contents($migrationFilepath, $template);
        $output->newline();
        $output->successLabel();
        $output->newline();
        $output->success("✓ Migration created in {$migrationFilepath}");
    }

    protected function parseSupportArgument(array $arguments): ?string
    {
        foreach ($arguments as $arg) {
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
            $output->infoLabel();
            $output->info("Tip: You can use --support=<schema> for a predefined migration. Supported: " . implode(', ', array_keys($schemas)) . ".");
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
            'social' => \Lightpack\Console\Views\Migrations\SocialView::class,
            'uploads' => \Lightpack\Console\Views\Migrations\UploadsView::class,
            'secrets' => \Lightpack\Console\Views\Migrations\SecretsView::class,
            'settings' => \Lightpack\Console\Views\Migrations\SettingsView::class,
            'sessions' => \Lightpack\Console\Views\Migrations\SessionsView::class,
            'taxonomies' => \Lightpack\Console\Views\Migrations\TaxonomiesView::class,
        ];
    }
}
