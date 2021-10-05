<?php

namespace Lightpack\Console;

use Lightpack\Console\Commands\CreateEnv;
use Lightpack\Console\Commands\CreateEvent;
use Lightpack\Console\Commands\CreateModel;
use Lightpack\Console\Commands\CreateFilter;
use Lightpack\Console\Commands\CreateCommand;
use Lightpack\Console\Commands\LinkStorage;
use Lightpack\Console\Commands\UnlinkStorage;
use Lightpack\Console\Commands\CreateProvider;
use Lightpack\Console\Commands\CreateMigration;
use Lightpack\Console\Commands\CreateController;
use Lightpack\Console\Commands\CreateJob;
use Lightpack\Console\Commands\RunMigrationUp;
use Lightpack\Console\Commands\RunMigrationDown;
use Lightpack\Console\Commands\CreateRequest;
use Lightpack\Console\Commands\ProcessJobs;
use Lightpack\Console\Commands\CreateMail;
use Lightpack\Console\Commands\CreateSeeder;
use Lightpack\Console\Commands\SeedCommand;

class Console
{
    private static $commands = [
        'create:env' => CreateEnv::class,
        'create:event' => CreateEvent::class,
        'create:model' => CreateModel::class,
        'create:filter' => CreateFilter::class,
        'create:command' => CreateCommand::class,
        'link:storage' => LinkStorage::class,
        'unlink:storage' => UnlinkStorage::class,
        'create:provider' => CreateProvider::class,
        'create:migration' => CreateMigration::class,
        'create:controller' => CreateController::class,
        'migrate:up' => RunMigrationUp::class,
        'migrate:down' => RunMigrationDown::class,
        'create:request' => CreateRequest::class,
        'process:jobs' => ProcessJobs::class,
        'create:job' => CreateJob::class,
        'create:mail' => CreateMail::class,
        'create:seeder' => CreateSeeder::class,
        'db:seed' => SeedCommand::class,
    ];

    public static function register(string $command, ICommand $handler)
    {
        self::$commands[$command] = $handler;
    }

    public static function getCommandHandler(string $command)
    {
        if (!isset(self::$commands[$command])) {
            fputs(STDERR, "Invalid command: {$command}\n");
            exit(0);
        }

        return new self::$commands[$command];
    }

    public static function run(string $command = null, array $arguments = [])
    {
        if ($command === null) {
            fputs(STDOUT, "Need help? https://lightpack.github.io/docs/#/console\n");
            exit(0);
        }

        $handler = self::getCommandHandler($command);
        $handler->run($arguments);
    }

    public static function getCommands()
    {
        return self::$commands;
    }

    public static function bootstrap()
    {   
        foreach(self::$commands as $command => $handler) {
            self::register($command, new $handler);
        }
    }
}
