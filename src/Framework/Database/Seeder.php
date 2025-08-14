<?php

namespace Lightpack\Database;

use Lightpack\Container\Container;

class Seeder
{
    public function run(string|array $seeder)
    {
        if (!is_array($seeder)) {
            $seeder = [$seeder];
        }

        fputs(STDOUT, "\nRunning seeders...\n\n");

        foreach ($seeder as $class) {
            Container::getInstance()->call($class, 'seed');
            fputs(STDOUT, "Seeded: {$class}\n");
        }
        fputs(STDOUT, "\nTotal seeders executed: " . count($seeder) . "\n");
    }
}
