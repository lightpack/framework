<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\Views\MailView;

class CreateMail extends Command
{
    public function run(): int
    {
        $className = $this->args->argument(0);
        $force = $this->args->has('force');

        if (null === $className) {
            $this->output->error("Please provide the mail class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid mail class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        $directory = './app/Mails';
        $filePath = DIR_ROOT . '/app/Mails/' . $className . '.php';

        if (file_exists($filePath) && !$force) {
            $this->output->newline();
            $this->output->error("Mail already exists: {$directory}/{$className}.php");
            $this->output->newline();
            $this->output->line("Use --force to overwrite.");
            $this->output->newline();
            return self::FAILURE;
        }

        $template = MailView::getTemplate();
        $template = str_replace('__MAIL_NAME__', $className, $template);

        file_put_contents($filePath, $template);
        $this->output->success("✓ Mail created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
