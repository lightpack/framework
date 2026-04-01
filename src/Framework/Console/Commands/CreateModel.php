<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;
use Lightpack\Console\Views\ModelView;
use Lightpack\Utils\Str;

class CreateModel extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $className = $this->args->argument(0);
        $tableName = $this->args->get('table');
        $primaryKey = $this->args->get('key');
        $isTenant = $this->args->has('tenant');
        
        if (!$className) {
            $this->output->error("Please provide a model class name.");
            $this->output->newline();
            return 1;
        }
        
        $paths = $this->resolvePaths($className);
        if ($paths === null) return 1;
        extract($paths); // $baseName, $subdir, $directory, $filePath, $parts

        if (!$this->validateSegments($parts, $baseName)) return 1;
        if (file_exists($filePath)) {
            $this->output->error("[Skipped]: ");
            $this->output->line("Model already exists at app/Models" . ($subdir ? "/$subdir" : '') . "/{$baseName}.php");
            return 1;
        }
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $tableName = $tableName ?? $this->createTableName($baseName);
        $primaryKey = $primaryKey ?? 'id';
        $namespace = $this->computeNamespace($subdir);
        $this->writeModelFile($filePath, $namespace, $baseName, $tableName, $primaryKey, $isTenant);
        
        $modelType = $isTenant ? 'Tenant model' : 'Model';
        $this->output->success("✓ {$modelType} created: app/Models" . ($subdir ? "/$subdir" : '') . "/{$baseName}.php");
        $this->output->newline();
        
        return 0;
    }

    private function resolvePaths(string $className)
    {
        $relativePath = str_replace('\\', '/', $className);
        if (strpos($relativePath, '.') !== false) {
            $this->output->error("Dot notation is not allowed in model names. Use slashes for subdirectories (e.g., Admin/User).");
            return null;
        }
        $parts = explode('/', $relativePath);
        $baseName = array_pop($parts);
        $subdir = implode('/', $parts);
        $directory = DIR_ROOT . '/app/Models' . ($subdir ? '/' . $subdir : '');
        $filePath = $directory . '/' . $baseName . '.php';
        return compact('baseName', 'subdir', 'directory', 'filePath', 'parts');
    }

    private function validateSegments(array $parts, string $baseName): bool
    {
        foreach (array_merge($parts, [$baseName]) as $segment) {
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $segment)) {
                $this->output->error("Invalid model or namespace segment: {$segment}. Each segment must start with a letter and contain only letters, numbers, or underscores.");
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
