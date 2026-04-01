<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;
use Lightpack\Console\Views\JobView;

class CreateJob extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide the job class name.");
            $this->output->newline();
            return 1;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid job class name.");
            $this->output->newline();
            return 1;
        }

        $template = JobView::getTemplate();
        $template = str_replace('__JOB_NAME__', $className, $template);
        $directory = './app/Jobs';

        file_put_contents(DIR_ROOT . '/app/Jobs/' . $className . '.php', $template);
        $this->output->success("✓ Job created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return 0;
    }
}
