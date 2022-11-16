<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Database\Seeders\DatabaseSeeder;

class SeedCommand implements ICommand
{
    public function run(array $arguments = [])
    {
        fputs(STDOUT, "\n");

        $confirm = readline('Are you sure you want to continue? (y/n) ');

        if(strtolower($confirm) === 'y') {
            (new DatabaseSeeder)->seed();
            fputs(STDOUT, "\n✔ Database seeded\n\n");
        } else {
            fputs(STDOUT, "\n✔ Database seeding cancelled\n\n");
        }
    }
}