<?php

namespace Lightpack\Console;

use Lightpack\Console\Commands\CreateEvent;
use Lightpack\Console\Commands\CreateModel;
use Lightpack\Console\Commands\CreateFilter;
use Lightpack\Console\Commands\CreateCommand;
use Lightpack\Console\Commands\CreateConfig;
use Lightpack\Console\Commands\LinkStorage;
use Lightpack\Console\Commands\UnlinkStorage;
use Lightpack\Console\Commands\CreateProvider;
use Lightpack\Console\Commands\CreateMigration;
use Lightpack\Console\Commands\CreateController;
use Lightpack\Console\Commands\CreateEnv;
use Lightpack\Console\Commands\CreateJob;
use Lightpack\Console\Commands\RunMigrationUp;
use Lightpack\Console\Commands\RunMigrationDown;
use Lightpack\Console\Commands\CreateRequest;
use Lightpack\Console\Commands\ProcessJobs;
use Lightpack\Console\Commands\CreateMail;
use Lightpack\Console\Commands\CreateSeeder;
use Lightpack\Console\Commands\CreateTransformer;
use Lightpack\Console\Commands\GenerateAppKey;
use Lightpack\Console\Commands\SeedCommand;
use Lightpack\Console\Commands\ScheduleEvents;
use Lightpack\Console\Commands\ServeCommand;
use Lightpack\Console\Commands\WatchCommand;
use Lightpack\Console\Commands\RetryFailedJobs;
use Lightpack\Console\Commands\CreateTool;

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
        'create:transformer' => CreateTransformer::class,
        'migrate:up' => RunMigrationUp::class,
        'migrate:down' => RunMigrationDown::class,
        'create:request' => CreateRequest::class,
        'process:jobs' => ProcessJobs::class, // deprecated, use jobs:run instead
        'jobs:run' => ProcessJobs::class,
        'create:job' => CreateJob::class,
        'create:mail' => CreateMail::class,
        'create:seeder' => CreateSeeder::class,
        'create:config' => CreateConfig::class,
        'db:seed' => SeedCommand::class,
        'schedule:events' => ScheduleEvents::class,
        'app:key' => GenerateAppKey::class,
        'app:serve' => ServeCommand::class,
        'watch' => WatchCommand::class,
        'jobs:retry' => RetryFailedJobs::class,
        'create:tool' => CreateTool::class,
    ];

    public static function register(string $command, CommandInterface $handler)
    {
        self::$commands[$command] = $handler;
    }

    public static function getCommandHandler(string $command)
    {
        if (!isset(self::$commands[$command])) {
            fputs(STDERR, "Invalid command: {$command}\n");
            exit(1);
        }

        return new self::$commands[$command];
    }

    public static function run(?string $command = null, array $arguments = [])
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
