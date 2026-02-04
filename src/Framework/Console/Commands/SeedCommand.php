<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Database\Seeders\DatabaseSeeder;

class SeedCommand implements CommandInterface
{
    public function run(array $arguments = [])
    {
        fputs(STDOUT, "\n");

        // Extract class name and flags
        $className = null;
        $force = false;
        
        foreach ($arguments as $arg) {
            if ($arg === '--force') {
                $force = true;
            } elseif (!str_starts_with($arg, '--')) {
                $className = $arg;
            }
        }

        // Default to DatabaseSeeder if no class specified
        if (!$className) {
            $className = 'DatabaseSeeder';
        }

        // Build fully qualified class name
        $fullyQualifiedClass = "Database\\Seeders\\{$className}";

        // Check if class exists
        if (!class_exists($fullyQualifiedClass)) {
            fputs(STDOUT, "✖ Seeder class '{$fullyQualifiedClass}' not found\n\n");
            return;
        }

        if ($force) {
            $confirm = 'y';
        } else {
            $confirm = readline('Are you sure you want to continue? (y/n) ');
        }

        if(strtolower($confirm) === 'y') {
            (new $fullyQualifiedClass)->seed();
            fputs(STDOUT, "\n✔ Seeded: {$className}\n\n");
        } else {
            fputs(STDOUT, "\n✔ Database seeding cancelled\n\n");
        }
    }
}