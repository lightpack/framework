<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Output;
use Lightpack\File\File;

class WatchCommand implements ICommand
{
    private $paths = [];
    private $extensions = [];
    private $fileHashes = [];
    private $isFirstRun = true;
    private $output;
    private $file;

    public function __construct()
    {
        $this->output = new Output();
        $this->file = new File();
    }

    public function run(array $arguments = [])
    {
        if (in_array('--help', $arguments)) {
            $this->showHelp();
            return;
        }

        // Parse arguments
        $isTest = in_array('--test', $arguments);
        $paths = $this->getOptionValue($arguments, '--path');
        $extensions = $this->getOptionValue($arguments, '--ext');
        $command = $this->getOptionValue($arguments, '--run');

        if (!$paths) {
            $this->output->error('No paths specified. Use --path=<paths> option.');
            return;
        }

        $this->addPaths($paths);

        if (empty($this->paths)) {
            $this->output->error('No valid paths found.');
            return;
        }

        if ($extensions) {
            $this->extensions = array_map('trim', explode(',', $extensions));
            $this->output->info("📎 File extensions: ." . implode(', .', $this->extensions));
        }

        $this->output->info("🔍 Watching for changes in:");
        foreach ($this->paths as $path) {
            $this->output->info("  - {$path}");
        }

        $this->output->info("✨ Ready! Press Ctrl+C to stop.");

        $this->updateFileHashes();

        if ($isTest) {
            return;
        }

        while (true) {
            if ($this->checkForChanges() && $command) {
                $this->output->info("🚀 Running command: {$command}");
                // Use shell_exec to support aliases and shell features
                shell_exec("/bin/zsh -i -c '{$command}'");
            }
            sleep(1);
        }
    }

    private function showHelp()
    {
        $this->output->info("Watch files and directories for changes");
        $this->output->info("Usage:");
        $this->output->info("  php console watch [options]");
        $this->output->info("Options:");
        $this->output->info("  --path=<paths>     Comma-separated paths to watch");
        $this->output->info("  --ext=<extensions> Comma-separated file extensions to watch");
        $this->output->info("  --run=<command>    Command to run when changes are detected");
        $this->output->warning("                   ⚠️  Uses shell, be careful with untrusted input");
        $this->output->info("Examples:");
        $this->output->info("  php console watch --path=app,config,routes");
        $this->output->info("  php console watch --path=app,config --ext=php,json");
        $this->output->info("  php console watch --path=app --ext=php --run=\"vendor/bin/phpunit\"");
    }

    private function getOptionValue(array $args, string $option): ?string
    {
        foreach ($args as $arg) {
            // Only support --option=value format
            if (strpos($arg, $option . '=') === 0) {
                return substr($arg, strlen($option) + 1);
            }
        }
        
        return null;
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
                $files = $this->file->traverse($path);
                if ($files === null) {
                    continue;
                }

                foreach ($files as $file) {
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
                    $this->output->error("🗑️  Deleted: {$relativePath}");
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
                    $this->output->warning("📝 Changed: {$relativePath}");
                    $changed = true;
                }
                continue;
            }

            try {
                $files = $this->file->traverse($path);
                if ($files === null) {
                    continue;
                }

                foreach ($files as $file) {
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
                        $this->output->warning("📝 Changed: {$relativePath}");
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
                    $this->output->error("🗑️  Deleted: {$relativePath}");
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
