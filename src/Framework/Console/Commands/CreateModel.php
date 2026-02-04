<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Console\Views\ModelView;
use Lightpack\Console\Output;
use Lightpack\Utils\Str;

class CreateModel implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $output = new Output();
        $options = $this->parseArguments($arguments, $output);
        if ($options === null) return;
        extract($options); // $className, $tableName, $primaryKey, $isTenant, $tenantColumn

        $paths = $this->resolvePaths($className, $output);
        if ($paths === null) return;
        extract($paths); // $baseName, $subdir, $directory, $filePath, $parts

        if (!$this->validateSegments($parts, $baseName, $output)) return;
        if (file_exists($filePath)) {
            $output->error("[Skipped]: ");
            $output->line("Model already exists at app/Models" . ($subdir ? "/$subdir" : '') . "/{$baseName}.php");
            return;
        }
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $tableName = $tableName ?? $this->createTableName($baseName);
        $primaryKey = $primaryKey ?? 'id';
        $namespace = $this->computeNamespace($subdir);
        $this->writeModelFile($filePath, $namespace, $baseName, $tableName, $primaryKey, $isTenant);
        
        $modelType = $isTenant ? 'Tenant model' : 'Model';
        $output->success("✓ {$modelType} created: app/Models" . ($subdir ? "/$subdir" : '') . "/{$baseName}.php");
        $output->newline();
    }

    private function parseArguments(array $arguments, Output $output)
    {
        if (empty($arguments)) {
            $output->error("Please provide a model class name.");
            $output->newline();
            return null;
        }
        $className = null;
        $tableName = null;
        $primaryKey = null;
        $isTenant = false;
        
        foreach ($arguments as $arg) {
            if (strpos($arg, '--table=') === 0) {
                $tableName = substr($arg, 8);
            } elseif (strpos($arg, '--key=') === 0) {
                $primaryKey = substr($arg, 6);
            } elseif ($arg === '--tenant') {
                $isTenant = true;
            } elseif ($className === null) {
                $className = $arg;
            }
        }
        
        if ($className === null) {
            $output->error("Please provide a model class name.\n");
            return null;
        }
        
        return compact('className', 'tableName', 'primaryKey', 'isTenant');
    }

    private function resolvePaths(string $className, Output $output)
    {
        $relativePath = str_replace('\\', '/', $className);
        if (strpos($relativePath, '.') !== false) {
            $output->error("Dot notation is not allowed in model names. Use slashes for subdirectories (e.g., Admin/User).");
            return null;
        }
        $parts = explode('/', $relativePath);
        $baseName = array_pop($parts);
        $subdir = implode('/', $parts);
        $directory = DIR_ROOT . '/app/Models' . ($subdir ? '/' . $subdir : '');
        $filePath = $directory . '/' . $baseName . '.php';
        return compact('baseName', 'subdir', 'directory', 'filePath', 'parts');
    }

    private function validateSegments(array $parts, string $baseName, Output $output): bool
    {
        foreach (array_merge($parts, [$baseName]) as $segment) {
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $segment)) {
                $output->error("Invalid model or namespace segment: {$segment}. Each segment must start with a letter and contain only letters, numbers, or underscores.");
                return false;
            }
        }
        return true;
    }

    private function computeNamespace(string $subdir): string
    {
        return 'App\\Models' . ($subdir ? '\\' . str_replace('/', '\\', $subdir) : '');
    }

    private function writeModelFile(string $filePath, string $namespace, string $baseName, string $tableName, string $primaryKey, bool $isTenant): void
    {
        if ($isTenant) {
            $template = ModelView::getTenantTemplate();
            $template = str_replace(
                ['__NAMESPACE__', '__MODEL_NAME__', '__TABLE_NAME__', '__PRIMARY_KEY__'],
                [$namespace, $baseName, $tableName, $primaryKey],
                $template
            );
        } else {
            $template = ModelView::getTemplate();
            $template = str_replace(
                ['__NAMESPACE__', '__MODEL_NAME__', '__TABLE_NAME__', '__PRIMARY_KEY__'],
                [$namespace, $baseName, $tableName, $primaryKey],
                $template
            );
        }
        file_put_contents($filePath, $template);
    }

    private function createTableName(string $text)
    {
        $text = str_replace('Model', '', $text);
        return (new Str)->tableize($text);
    }
}
