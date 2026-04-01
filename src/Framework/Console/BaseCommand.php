<?php

namespace Lightpack\Console;

/**
 * Base class for all console commands.
 * Provides automatic access to Args, Output, and Prompt utilities.
 */
abstract class BaseCommand implements CommandInterface
{
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
     * Execute the command
     * Subclasses implement this method with their logic
     */
    abstract public function run(array $arguments = []): int;
}
