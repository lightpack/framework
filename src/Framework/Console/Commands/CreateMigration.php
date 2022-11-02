<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\MigrationView;

class CreateMigration implements ICommand
{
    public function run(array $arguments = [])
    {
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

        file_put_contents($migrationFilepath, $template);
        fputs(STDOUT, "✓ Migration created in ./database/migrations directory.\n\n");
    }
}
