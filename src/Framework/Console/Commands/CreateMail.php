<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\MailView;

class CreateMail implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide the mail class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid mail class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = MailView::getTemplate();
        $template = str_replace('__MAIL_NAME__', $className, $template);
        $directory = './app/Mails';

        file_put_contents(DIR_ROOT . '/app/Mails/' . $className . '.php', $template);
        fputs(STDOUT, "✓ Mail created: {$directory}/{$className}.php\n\n");
    }
}
