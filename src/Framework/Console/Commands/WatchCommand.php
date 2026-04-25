<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\File\File;

class WatchCommand extends Command
{
    private $paths = [];
    private $extensions = [];
    private $fileHashes = [];
    private $isFirstRun = true;
    private $file;

    public function run()
    {
        $this->file = new File();
        
        if ($this->args->has('help')) {
            $this->showHelp();
            return self::SUCCESS;
        }

        $isTest = $this->args->has('test');
        $paths = $this->args->get('path');
        $extensions = $this->args->get('ext');
        $command = $this->args->get('run');

        if (!$paths) {
            $this->output->newline();
            $this->output->error('No paths specified. Use --path=<paths> option.');
            $this->output->newline();

            return self::FAILURE;
        }

        $this->addPaths($paths);

        if (empty($this->paths)) {
            $this->output->newline();
            $this->output->error('No valid paths found.');
            $this->output->newline();

            return self::FAILURE;
        }

        if ($extensions) {
            $this->extensions = array_map('trim', explode(',', $extensions));
            $this->output->newline();
            $this->output->info("📎 File extensions: ." . implode(', .', $this->extensions));
            $this->output->newline();
        }

        $this->output->newline();
        $this->output->info("🔍 Watching for changes in:");
        $this->output->newline();

        foreach ($this->paths as $path) {
            $this->output->info("  - {$path}");
            $this->output->newline();
        }

        $this->output->info("✨ Ready! Press Ctrl+C to stop.");
        $this->output->newline();

        $this->updateFileHashes();

        if ($isTest) {
            return self::SUCCESS;
        }

        while (true) {
            if ($this->checkForChanges() && $command) {
                $this->output->newline();
                $this->output->info("🚀 Running command: {$command}");
                $this->output->newline();

                $shell = getenv('SHELL') ?: '/bin/sh';
                shell_exec("$shell -c '$command'");
            }
            sleep(1);
        }
        
        return self::SUCCESS;
    }

    private function showHelp()
    {
        $this->output->info("Watch files and directories for changes");
        $this->output->newline();

        $this->output->info("Usage:");
        $this->output->newline();

        $this->output->info("  php console watch [options]");
        $this->output->newline();

        $this->output->info("Options:");
        $this->output->newline();

        $this->output->info("  --path=<paths>     Comma-separated paths to watch");
        $this->output->newline();

        $this->output->info("  --ext=<extensions> Comma-separated file extensions to watch");
        $this->output->newline();

        $this->output->info("  --run=<command>    Command to run when changes are detected");
        $this->output->newline();

        $this->output->warning("                   ⚠️  Uses shell, be careful with untrusted input");
        $this->output->newline();

        $this->output->info("Examples:");
        $this->output->newline();

        $this->output->info("  php console watch --path=app,config,routes");
        $this->output->newline();

        $this->output->info("  php console watch --path=app,config --ext=php,json");
        $this->output->newline();

        $this->output->info("  php console watch --path=app --ext=php --run=\"vendor/bin/phpunit\"");
        $this->output->newline();

    }

    private function addPaths(string $pathString)
    {
        $paths = explode(',', $pathString);
        foreach ($paths as $path) {
            $path = trim($path);

            // If path is absolute, use it directly
            if (str_starts_with($path, '/')) {
                $fullPath = $path;
            } else {
                // Otherwise, make it absolute from current directory
                $fullPath = getcwd() . '/' . trim($path, '/');
            }

            $fullPath = $this->file->sanitizePath($fullPath);
            if ($this->file->exists($fullPath) && !in_array($fullPath, $this->paths)) {
                $this->paths[] = $fullPath;
            }
        }
    }

    private function updateFileHashes()
    {
        $this->fileHashes = [];
        foreach ($this->paths as $path) {
            if (!$this->file->exists($path)) {
                continue;
            }

            if (!$this->file->isDir($path)) {
                $ext = $this->file->extension($path);
                if (!empty($this->extensions) && !in_array($ext, $this->extensions)) {
                    continue;
                }

                $this->fileHashes[$path] = $this->getFileHash($path);
                continue;
            }

            try {
                $iterator = $this->file->getRecursiveIterator($path);
                if ($iterator === null) {
                    continue;
                }

                foreach ($iterator as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }

                    $filePath = $file->getRealPath();
                    $ext = $this->file->extension($filePath);
                    if (!empty($this->extensions) && !in_array($ext, $this->extensions)) {
                        continue;
                    }

                    $this->fileHashes[$filePath] = $this->getFileHash($filePath);
                }
            } catch (\UnexpectedValueException $e) {
                continue;
            }
        }
    }

    private function checkForChanges(): bool
    {
        $currentHashes = [];
        $changed = false;

        foreach ($this->paths as $path) {
            if (!$this->file->exists($path)) {
                if (!$this->isFirstRun && isset($this->fileHashes[$path])) {
                    $relativePath = str_replace(getcwd() . '/', '', $path);
        
                    $this->output->newline();
                    $this->output->error("🗑️  Deleted: {$relativePath}");
                    $this->output->newline();

                    $changed = true;
                }
                continue;
            }

            if (!$this->file->isDir($path)) {
                $ext = $this->file->extension($path);
                if (!empty($this->extensions) && !in_array($ext, $this->extensions)) {
                    continue;
                }

                $currentHash = $this->getFileHash($path);
                $currentHashes[$path] = $currentHash;

                if (!$this->isFirstRun && (!isset($this->fileHashes[$path]) || $this->fileHashes[$path] !== $currentHash)) {
                    $relativePath = str_replace(getcwd() . '/', '', $path);
        
                    $this->output->newline();
                    $this->output->warning("📝 Changed: {$relativePath}");
                    $this->output->newline();

                    $changed = true;
                }
                continue;
            }

            try {
                $iterator = $this->file->getRecursiveIterator($path);
                if ($iterator === null) {
                    continue;
                }

                foreach ($iterator as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }

                    $filePath = $file->getRealPath();
                    $ext = $this->file->extension($filePath);
                    if (!empty($this->extensions) && !in_array($ext, $this->extensions)) {
                        continue;
                    }

                    $currentHash = $this->getFileHash($filePath);
                    $currentHashes[$filePath] = $currentHash;

                    if (!$this->isFirstRun && (!isset($this->fileHashes[$filePath]) || $this->fileHashes[$filePath] !== $currentHash)) {
                        $relativePath = str_replace(getcwd() . '/', '', $filePath);

                        $this->output->newline();
                        $this->output->warning("📝 Changed: {$relativePath}");
                        $this->output->newline();

                        $changed = true;
                    }
                }
            } catch (\UnexpectedValueException $e) {
                continue;
            }
        }

        // Check for deleted files
        if (!$this->isFirstRun) {
            foreach ($this->fileHashes as $filePath => $hash) {
                if (!isset($currentHashes[$filePath])) {
                    $relativePath = str_replace(getcwd() . '/', '', $filePath);

                    $this->output->newline();
                    $this->output->error("🗑️  Deleted: {$relativePath}");
                    $this->output->newline();

                    $changed = true;
                }
            }
        }

        $this->fileHashes = $currentHashes;
        $this->isFirstRun = false;

        return $changed;
    }

    private function getFileHash(string $path): string
    {
        return md5_file($path) ?: '';
    }
}
