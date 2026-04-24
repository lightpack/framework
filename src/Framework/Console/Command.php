<?php

namespace Lightpack\Console;

/**
 * Base class for all console commands.
 * Provides automatic access to Args, Output, and Prompt utilities.
 */
abstract class Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    
    protected Args $args;
    protected Output $output;
    protected Prompt $prompt;
    
    /**
     * Initialize command with dependencies
     */
    public function __construct(array $arguments = [])
    {
        $this->args = new Args($arguments);
        $this->output = new Output();
        $this->prompt = new Prompt();
    }
    
    /**
     * Execute the command.
     *
     * Commands may return an integer exit code (0 for success, non-zero for failure)
     * or omit the return entirely, in which case the command is treated as successful.
     */
    abstract public function run();
}
