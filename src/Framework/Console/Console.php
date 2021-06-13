<?php

namespace Lightpack\Console;

use Lightpack\Console\Commands\CreateEvent;
use Lightpack\Console\Commands\CreateModel;
use Lightpack\Console\Commands\CreateFilter;
use Lightpack\Console\Commands\CreateCommand;
use Lightpack\Console\Commands\CreateController;

class Console
{
    private static $commands = [
        'create:event' => CreateEvent::class,
        'create:model' => CreateModel::class,
        'create:filter' => CreateFilter::class,
        'create:command' => CreateCommand::class,
        'create:controller' => CreateController::class,
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

        return self::$commands[$command];
    }

    public static function runCommand(string $command = null, array $arguments = [])
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
