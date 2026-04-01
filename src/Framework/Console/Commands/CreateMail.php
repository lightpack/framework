<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;
use Lightpack\Console\Views\MailView;

class CreateMail extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide the mail class name.");
            $this->output->newline();
            return 1;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid mail class name.");
            $this->output->newline();
            return 1;
        }

        $template = MailView::getTemplate();
        $template = str_replace('__MAIL_NAME__', $className, $template);
        $directory = './app/Mails';

        file_put_contents(DIR_ROOT . '/app/Mails/' . $className . '.php', $template);
        $this->output->success("✓ Mail created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return 0;
    }
}
