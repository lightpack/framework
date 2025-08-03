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
        if ($support && isset($schemas[$support])) {
            // If --support is present and valid, use it for migration naming and template
            $migration = date('YmdHis') . '_' . $support . '_schema';
            $migrationFilepath = './database/migrations/' . $migration . '.php';
            $template = $schemas[$support]::getTemplate();
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
            'users' => \Lightpack\Console\Views\Migrations\UserSchemaView::class,
        ];
    }
}
