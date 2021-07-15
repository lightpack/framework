<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\ModelView;

class CreateModel implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;
        $tableName = $this->parseTableName($arguments);
        $primaryKey = $this->parsePrimaryKey($arguments) ?? 'id';

        if (null === $className) {
            $message = "Please provide a model class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('#[A-Za-z0-9]#', $className)) {
            $message = "Invalid model class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (null === $className) {
            $message = "Please provide a model class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (null === $tableName) {
            $message = "Please provide the table name as --table flag.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = ModelView::getTemplate();
        $template = str_replace(
            ['__MODEL_NAME__', '__TABLE_NAME__', '__PRIMARY_KEY__'],
            [$className, $tableName, $primaryKey],
            $template
        );
        $directory = 'app/Models';

        file_put_contents(DIR_ROOT . '/app/Models/' . $className . '.php', $template);
        fputs(STDOUT, "✓ Model created: {$directory}/{$className}.php\n\n");
    }

    private function parseTableName(array $arguments)
    {
        foreach ($arguments as $arg) {
            if (strpos($arg, '--table') === 0) {
                $tableName = explode('=', $arg)[1] ?? null;

                if (preg_match('#[A-Za-z0-9]#', $tableName)) {
                    return $tableName;
                }
            }
        }
    }

    private function parsePrimaryKey(array $arguments)
    {
        foreach ($arguments as $arg) {
            if (strpos($arg, '--key') === 0) {
                $key = explode('=', $arg)[1] ?? null;

                if (!preg_match('#[A-Za-z0-9_]#', $key)) {
                    $message = "The --key flag must only contain alphabest and underscore.\n\n";
                    fputs(STDERR, $message);
                    exit(1);
                }

                return $key;
            }
        }
    }
}
