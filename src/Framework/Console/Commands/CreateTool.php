<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\Views\ToolView;

class CreateTool extends Command
{
    public function run(): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide a tool class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid tool class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        $template = ToolView::getTemplate();
        $template = str_replace('__TOOL_NAME__', $className, $template);
        $directory = './app/Tools';

        if (!is_dir(DIR_ROOT . '/app/Tools')) {
            mkdir(DIR_ROOT . '/app/Tools', 0755, true);
        }

        file_put_contents(DIR_ROOT . '/app/Tools/' . $className . '.php', $template);
        $this->output->success("✓ Tool created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
