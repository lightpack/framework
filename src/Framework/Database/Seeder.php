<?php

namespace Lightpack\Database;

use Lightpack\Console\Output;
use Lightpack\Container\Container;

class Seeder
{
    public function run(string|array $seeder)
    {
        if (!is_array($seeder)) {
            $seeder = [$seeder];
        }

        // fputs(STDOUT, "\nRunning seeders...\n\n");
        $output = new Output;
        $output->newline();
        $output->info('Running seeders...');
        $output->newline(2);

        foreach ($seeder as $class) {
            Container::getInstance()->call($class, 'seed');
            $output->success("✓");
            $output->line(" {$class}");
        }

        $output->newline();
        $output->success('[DONE]:');
        $output->line(" Total seeders executed: " . count($seeder));
    }
}
