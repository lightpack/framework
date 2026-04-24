<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\Views\ToolView;

class CreateTool extends Command
{
    public function run()
    {
        $className = $this->args->argument(0);
        $force = $this->args->has('force');

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

        $directory = './app/Tools';
        $filePath = DIR_ROOT . '/app/Tools/' . $className . '.php';

        if (!is_dir(DIR_ROOT . '/app/Tools')) {
            mkdir(DIR_ROOT . '/app/Tools', 0755, true);
        }

        if (file_exists($filePath) && !$force) {
            $this->output->newline();
            $this->output->error("Tool already exists: {$directory}/{$className}.php");
            $this->output->newline();
            $this->output->line("Use --force to overwrite.");
            $this->output->newline();
            return self::FAILURE;
        }

        $template = ToolView::getTemplate();
        $template = str_replace('__TOOL_NAME__', $className, $template);

        file_put_contents($filePath, $template);
        $this->output->success("✓ Tool created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
