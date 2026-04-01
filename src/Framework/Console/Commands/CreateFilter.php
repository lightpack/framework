<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;
use Lightpack\Console\Views\FilterView;

class CreateFilter extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide a filter class name.");
            $this->output->newline();
            return 1;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid filter class name.");
            $this->output->newline();
            return 1;
        }

        $template = FilterView::getTemplate();
        $template = str_replace('__FILTER_NAME__', $className, $template);
        $directory = './app/Filters';

        file_put_contents(DIR_ROOT . '/app/Filters/' . $className . '.php', $template);
        $this->output->success("✓ Filter created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return 0;
    }
}
