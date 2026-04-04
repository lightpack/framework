<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\Views\JobView;

class CreateJob extends Command
{
    public function run(): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide the job class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid job class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        $template = JobView::getTemplate();
        $template = str_replace('__JOB_NAME__', $className, $template);
        $directory = './app/Jobs';

        file_put_contents(DIR_ROOT . '/app/Jobs/' . $className . '.php', $template);
        $this->output->success("✓ Job created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
