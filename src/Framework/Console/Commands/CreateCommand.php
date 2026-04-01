<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;
use Lightpack\Console\Views\CommandView;

class CreateCommand extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide a command class name.");
            $this->output->newline();
            return 1;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid command class name.");
            $this->output->newline();
            return 1;
        }

        $template = CommandView::getTemplate();
        $template = str_replace('__COMMAND_NAME__', $className, $template);
        $directory = './app/Commands';

        file_put_contents(DIR_ROOT . '/app/Commands/' . $className . '.php', $template);
        $this->output->success("✓ Command created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return 0;
    }
}
