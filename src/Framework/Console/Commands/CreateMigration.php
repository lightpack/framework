<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;

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
        $migrationUpFilepath = './database/migrations/up/' . $migration . '.sql';
        $migrationDownFilepath = './database/migrations/down/' . $migration . '.sql';

        file_put_contents($migrationUpFilepath, null);
        file_put_contents($migrationDownFilepath, null);

        fputs(STDOUT, "✓ Migration created in ./database/migrations directory.\n\n");
    }
}
