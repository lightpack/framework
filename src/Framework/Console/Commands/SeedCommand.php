<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Database\Seeders\DatabaseSeeder;

class SeedCommand implements ICommand
{
    public function run(array $arguments = [])
    {
        (new DatabaseSeeder)->seed();

        fputs(STDOUT, "✔ Database seeded\n\n");
    }
}