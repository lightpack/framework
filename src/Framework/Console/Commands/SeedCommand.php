<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;
use Database\Seeders\DatabaseSeeder;

class SeedCommand extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $this->output->newline();

        $className = $this->args->argument(0) ?? 'DatabaseSeeder';
        $force = $this->args->has('force');

        // Build fully qualified class name
        $fullyQualifiedClass = "Database\\Seeders\\{$className}";

        if (!class_exists($fullyQualifiedClass)) {
            $this->output->error("✖ Seeder class '{$fullyQualifiedClass}' not found");
            $this->output->newline();
            return 1;
        }

        $confirm = $force || $this->prompt->confirm('Are you sure you want to continue?', false);

        if($confirm) {
            (new $fullyQualifiedClass)->seed();
            $this->output->newline();
            $this->output->success("✔ Seeded: {$className}");
            $this->output->newline();
        } else {
            $this->output->newline();
            $this->output->success("✔ Database seeding cancelled");
            $this->output->newline();
        }
        
        return 0;
    }
}