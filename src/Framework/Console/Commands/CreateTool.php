<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Console\Views\ToolView;

class CreateTool implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide a tool class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid tool class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = ToolView::getTemplate();
        $template = str_replace('__TOOL_NAME__', $className, $template);
        $directory = './app/Tools';

        if (!is_dir(DIR_ROOT . '/app/Tools')) {
            mkdir(DIR_ROOT . '/app/Tools', 0755, true);
        }

        file_put_contents(DIR_ROOT . '/app/Tools/' . $className . '.php', $template);
        fputs(STDOUT, "✓ Tool created: {$directory}/{$className}.php\n\n");
    }
}
