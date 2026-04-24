<?php

namespace Lightpack\Console;

/**
 * This class identifies the CLI command to run
 * and invokes the appropriate command handler.
 */
class Runner
{
    public static function run()
    {
        global $argv;

        // Remove script name (php lightpack)
        $arguments = array_slice($argv, 1);
        
        // First argument is the command name
        $command = $arguments[0] ?? null;

        Console::run($command, $arguments);
    }
}