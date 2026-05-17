<?php

namespace Lightpack\Database;

use Lightpack\Console\Output;
use Lightpack\Container\Container;

class Seeder
{
    public function run(string|array $seeder)
    {
        if (! is_array($seeder)) {
            $seeder = [$seeder];
        }

        // fputs(STDOUT, "\nRunning seeders...\n\n");
        $output = new Output;
        $output->info('Running seeders...');
        $output->newline();

        foreach ($seeder as $class) {
            Container::getInstance()->call($class, 'seed');
            $output->success("✓ {$class}");
        }

        $output->newline();
        $output->success('[DONE]: Total seeders executed: ' . count($seeder));
    }
}
