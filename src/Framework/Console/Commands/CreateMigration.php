<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\MigrationView;

class CreateMigration implements ICommand
{
    public function run(array $arguments = [])
    {
        // Parse for --support argument first
        $support = null;
        foreach ($arguments as $arg) {
            if (strpos($arg, '--support=') === 0) {
                $support = substr($arg, strlen('--support='));
                break;
            }
        }

        $schemas = self::getPredefinedSchemas();
        if ($support) {
            if (isset($schemas[$support])) {
                // If --support is present and valid, use it for migration naming and template
                $migration = date('YmdHis') . '_' . $support . '_schema';
                $migrationFilepath = './database/migrations/' . $migration . '.php';
                $template = $schemas[$support]::getTemplate();
            } else {
                $message = "Unknown support schema: \"{$support}\".\n";
                $message .= "Supported values are: " . implode(', ', array_keys($schemas)) . ".\n\n";
                fputs(STDERR, $message);
                return;
            }
        } else {
            // Fallback to classic behavior (require migration name)
            $migration = $arguments[0] ?? null;

            if (null === $migration) {
                $message = "Please provide a migration file name.\n\n";
                fputs(STDERR, $message);
                return;
            }

            if (!preg_match('/^[\w_]+$/', $migration)) {
                $message = "Migration file name can only contain alphanumeric characters and underscores.\n\n";
                fputs(STDERR, $message);
                return;
            }

            $migration = date('YmdHis') . '_' . $migration;
            $migrationFilepath = './database/migrations/' . $migration . '.php';
            $template = MigrationView::getTemplate();
        }

        file_put_contents($migrationFilepath, $template);
        fputs(STDOUT, "✓ Migration created in {$migrationFilepath}\n\n");
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
            'cable' => \Lightpack\Console\Views\Migrations\UploadsView::class,
            'social' => \Lightpack\Console\Views\Migrations\UploadsView::class,
            'uploads' => \Lightpack\Console\Views\Migrations\UploadsView::class,
            'secrets' => \Lightpack\Console\Views\Migrations\SecretsView::class,
            'settings' => \Lightpack\Console\Views\Migrations\SettingsView::class,
            'sessions' => \Lightpack\Console\Views\Migrations\SessionsView::class,
            'taxonomies' => \Lightpack\Console\Views\Migrations\TaxonomiesView::class,
        ];
    }
}
