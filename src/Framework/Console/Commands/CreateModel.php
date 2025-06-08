<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\ModelView;
use Lightpack\Utils\Str;

class CreateModel implements ICommand
{
    public function run(array $arguments = [])
    {
        if (empty($arguments)) {
            fputs(STDERR, "Please provide one or more model class names.\n\n");
            return;
        }

        // Only support a single model per command
        $className = null;
        $tableName = null;
        $primaryKey = null;
        foreach ($arguments as $arg) {
            if (strpos($arg, '--table=') === 0) {
                $tableName = substr($arg, 8);
            } elseif (strpos($arg, '--key=') === 0) {
                $primaryKey = substr($arg, 6);
            } elseif ($className === null) {
                $className = $arg;
            }
        }
        if ($className === null) {
            fputs(STDERR, "Please provide a model class name.\n\n");
            return;
        }

        // Support subdirectories, e.g., Admin/User => app/Models/Admin/User.php
        $relativePath = str_replace('\\', '/', $className);
        $relativePath = str_replace('.', '/', $relativePath); // Support dot notation
        $parts = explode('/', $relativePath);
        $baseName = array_pop($parts);
        $subdir = implode('/', $parts);
        $directory = DIR_ROOT . '/app/Models' . ($subdir ? '/' . $subdir : '');
        $filePath = $directory . '/' . $baseName . '.php';

        // Validate class name (only allow alnum and underscore)
        if (!preg_match('/^[A-Za-z0-9_]+$/', $baseName)) {
            fputs(STDERR, "Invalid model class name: {$className}\n");
            return;
        }

        // Check if file exists
        if (file_exists($filePath)) {
            fputs(STDERR, "Skipped: Model already exists at app/Models" . ($subdir ? "/$subdir" : '') . "/{$baseName}.php\n");
            return;
        }

        // Ensure directory exists
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        // Table name
        $tableName = $tableName ?? $this->createTableName($baseName);
        // Primary key
        $primaryKey = $primaryKey ?? 'id';

        // Compute namespace
        $namespace = 'App\\Models' . ($subdir ? '\\' . str_replace('/', '\\', $subdir) : '');
        // Prepare template
        $template = ModelView::getTemplate();
        $template = str_replace(
            ['__NAMESPACE__', '__MODEL_NAME__', '__TABLE_NAME__', '__PRIMARY_KEY__'],
            [$namespace, $baseName, $tableName, $primaryKey],
            $template
        );

        file_put_contents($filePath, $template);
        fputs(STDOUT, "✓ Model created: app/Models" . ($subdir ? "/$subdir" : '') . "/{$baseName}.php\n");
    }
    
    private function createTableName(string $text)
    {
        $text = str_replace('Model', '', $text);

        return (new Str)->tableize($text);
    }
}
