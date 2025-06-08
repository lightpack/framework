<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\ModelView;
use Lightpack\Console\Output;
use Lightpack\Utils\Str;

class CreateModel implements ICommand
{
    public function run(array $arguments = [])
    {
        $output = new Output();
        if (empty($arguments)) {
            $output->error("Please provide a model class name.\n");
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
            $output->error("Please provide a model class name.\n");
            return;
        }

        // Support subdirectories, e.g., Admin/User => app/Models/Admin/User.php
        $relativePath = str_replace('\\', '/', $className);
        if (strpos($relativePath, '.') !== false) {
            $output->error("Dot notation is not allowed in model names. Use slashes for subdirectories (e.g., Admin/User).");
            return;
        }
        $parts = explode('/', $relativePath);
        $baseName = array_pop($parts);
        $subdir = implode('/', $parts);
        $directory = DIR_ROOT . '/app/Models' . ($subdir ? '/' . $subdir : '');
        $filePath = $directory . '/' . $baseName . '.php';

        // Validate all namespace/class segments
        foreach (array_merge($parts, [$baseName]) as $segment) {
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $segment)) {
                $output->error("Invalid model or namespace segment: {$segment}. Each segment must start with a letter and contain only letters, numbers, or underscores.");
                return;
            }
        }

        // Check if file exists
        if (file_exists($filePath)) {
            $output->warning("Skipped: Model already exists at app/Models" . ($subdir ? "/$subdir" : '') . "/{$baseName}.php");
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
        $output->success("✓ Model created: app/Models" . ($subdir ? "/$subdir" : '') . "/{$baseName}.php");
    }
    
    private function createTableName(string $text)
    {
        $text = str_replace('Model', '', $text);

        return (new Str)->tableize($text);
    }
}
