<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\JobView;

class CreateJob implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide the job class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid job class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = JobView::getTemplate();
        $template = str_replace('__JOB_NAME__', $className, $template);
        $directory = './app/Jobs';

        file_put_contents(DIR_ROOT . '/app/Jobs/' . $className . '.php', $template);
        fputs(STDOUT, "✓ Job created: {$directory}/{$className}.php\n\n");
    }
}
