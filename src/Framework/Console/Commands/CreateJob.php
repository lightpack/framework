<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\Views\JobView;

class CreateJob extends Command
{
    public function run(): int
    {
        $className = $this->args->argument(0);
        $force = $this->args->has('force');

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

        $directory = './app/Jobs';
        $filePath = DIR_ROOT . '/app/Jobs/' . $className . '.php';

        if (file_exists($filePath) && !$force) {
            $this->output->newline();
            $this->output->error("Job already exists: {$directory}/{$className}.php");
            $this->output->newline();
            $this->output->line("Use --force to overwrite.");
            $this->output->newline();
            return self::FAILURE;
        }

        $template = JobView::getTemplate();
        $template = str_replace('__JOB_NAME__', $className, $template);

        file_put_contents($filePath, $template);
        $this->output->success("✓ Job created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
