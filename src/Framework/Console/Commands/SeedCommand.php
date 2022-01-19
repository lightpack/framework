<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Database\Seeders\DatabaseSeeder;

class SeedCommand implements ICommand
{
    public function run(array $arguments = [])
    {
        $confirm = readline('Are you sure you want to continue? (y/n) ');

        if(strtolower($confirm) === 'y') {
            (new DatabaseSeeder)->seed();
            fputs(STDOUT, "✔ Database seeded\n\n");
        }
    }
}