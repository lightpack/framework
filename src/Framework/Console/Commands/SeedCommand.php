<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Database\Seeders\DatabaseSeeder;

class SeedCommand implements ICommand
{
    public function run(array $arguments = [])
    {
        fputs(STDOUT, "\n");

        if (in_array('--force', $arguments)) {
            $confirm = 'y';
        } else {
            $confirm = readline('Are you sure you want to continue? (y/n) ');
        }

        if(strtolower($confirm) === 'y') {
            (new DatabaseSeeder)->seed();
        } else {
            fputs(STDOUT, "\n✔ Database seeding cancelled\n\n");
        }
    }
}