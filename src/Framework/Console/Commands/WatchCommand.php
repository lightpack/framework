<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Output;

class WatchCommand implements ICommand
{
    private $output;
    private $paths = [];
    private $extensions = [];
    private $ignorePatterns = [];
    private $fileHashes = [];
    private $isFirstRun = true;
    private $command;

    public function __construct()
    {
        $this->output = new Output;
    }

    public function run(array $arguments = [])
    {
        if (empty($arguments)) {
            $this->showHelp();
            return;
        }

        // Parse arguments
        $this->parseArguments($arguments);

        if (empty($this->paths)) {
            $this->output->error("❌ No valid paths to watch.");
            $this->showHelp();
            return;
        }

        $this->output->info("🔍 Watching for changes in:");
        foreach ($this->paths as $path) {
            $this->output->info("  - {$path}");
        }

        if ($this->extensions) {
            $this->output->info("\n📎 File extensions: ." . implode(', .', $this->extensions));
        }

        if ($this->ignorePatterns) {
            $this->output->info("\n🚫 Ignoring patterns:");
            foreach ($this->ignorePatterns as $pattern) {
                $this->output->info("  - {$pattern}");
            }
        }

        if ($this->command) {
            $this->output->info("\n🔄 On change will run: {$this->command}");
        }
        
        $this->output->info("\n✨ Ready! Press Ctrl+C to stop.\n");
        
        // Initial file hash collection
        $this->updateFileHashes();
        
        // Watch loop
        while (true) {
            if ($this->checkForChanges()) {
                if ($this->command) {
                    $this->output->info("\n▶️  Running command...");
                    passthru($this->command);
                }
            }
            usleep(1000000); // 1 second
        }
    }

    private function showHelp()
    {
        $this->output->error("❌ Please specify what to watch.");
        $this->output->info("\nUsage:");
        $this->output->info("  php console watch [options]");
        $this->output->info("\nOptions:");
        $this->output->info("  --path <paths>     Comma-separated paths to watch");
        $this->output->info("  --ext <exts>       Comma-separated file extensions to watch");
        $this->output->info("  --ignore <patterns> Comma-separated patterns to ignore (e.g. vendor/*,cache/*)");
        $this->output->info("  --run <command>    Command to run when changes are detected");
        $this->output->info("\nExamples:");
        $this->output->info("  php console watch --path=app,config,routes");
        $this->output->info("  php console watch --path=app,config --ext=php,json");
        $this->output->info("  php console watch --path=app --ignore=cache/*,logs/* --ext=php");
        $this->output->info("  php console watch --path=app --ext=php --run=\"vendor/bin/phpunit\"");
    }

    private function parseArguments(array $arguments)
    {
        $i = 0;
        while ($i < count($arguments)) {
            $arg = $arguments[$i];
            
            switch ($arg) {
                case '--path':
                    if (isset($arguments[$i + 1])) {
                        $this->addPaths($arguments[++$i]);
                    }
                    break;
                    
                case '--ext':
                    if (isset($arguments[$i + 1])) {
                        $this->extensions = array_map('trim', explode(',', $arguments[++$i]));
                    }
                    break;
                    
                case '--ignore':
                    if (isset($arguments[$i + 1])) {
                        $this->ignorePatterns = array_map('trim', explode(',', $arguments[++$i]));
                    }
                    break;
                    
                case '--run':
                    if (isset($arguments[$i + 1])) {
                        $this->command = $arguments[++$i];
                    }
                    break;
                    
                default:
                    // For backward compatibility, treat bare arguments as paths
                    if (!str_starts_with($arg, '-')) {
                        $this->addPaths($arg);
                    }
            }
            
            $i++;
        }
    }

    private function shouldIgnore(string $path): bool
    {
        if (empty($this->ignorePatterns)) {
            return false;
        }

        $relativePath = str_replace(getcwd() . '/', '', $path);
        foreach ($this->ignorePatterns as $pattern) {
            if (fnmatch($pattern, $relativePath)) {
                return true;
            }
        }

        return false;
    }

    private function addPaths(string $pathString)
    {
        $paths = explode(',', $pathString);
        foreach ($paths as $path) {
            $path = trim($path);
            $fullPath = getcwd() . '/' . trim($path, '/');
            if (file_exists($fullPath)) {
                $this->paths[] = $fullPath;
            }
        }
    }

    private function updateFileHashes()
    {
        foreach ($this->paths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            if (is_file($path)) {
                if (!$this->shouldIgnore($path)) {
                    $this->fileHashes[$path] = $this->getFileHash($path);
                }
                continue;
            }
            
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $filePath = $file->getRealPath();
                
                if ($this->shouldIgnore($filePath)) {
                    continue;
                }

                if ($this->extensions && !in_array($file->getExtension(), $this->extensions)) {
                    continue;
                }

                $this->fileHashes[$filePath] = $this->getFileHash($filePath);
            }
        }
    }

    private function checkForChanges(): bool
    {
        $changed = false;
        $currentHashes = [];

        foreach ($this->paths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            if (is_file($path)) {
                if (!$this->shouldIgnore($path)) {
                    $currentHashes[$path] = $this->getFileHash($path);
                }
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $filePath = $file->getRealPath();

                if ($this->shouldIgnore($filePath)) {
                    continue;
                }

                if ($this->extensions && !in_array($file->getExtension(), $this->extensions)) {
                    continue;
                }

                $currentHash = $this->getFileHash($filePath);
                $currentHashes[$filePath] = $currentHash;

                // Skip comparison on first run
                if ($this->isFirstRun) {
                    continue;
                }

                // Check if file is new or modified
                if (!isset($this->fileHashes[$filePath]) || $this->fileHashes[$filePath] !== $currentHash) {
                    $relativePath = str_replace(getcwd() . '/', '', $filePath);
                    $this->output->warning("📝 Changed: {$relativePath}");
                    $changed = true;
                }
            }
        }

        // Check for deleted files
        if (!$this->isFirstRun) {
            foreach ($this->fileHashes as $filePath => $hash) {
                if (!isset($currentHashes[$filePath]) && !$this->shouldIgnore($filePath)) {
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

    private function getFileHash(string $filePath): string
    {
        return md5_file($filePath) ?: '';
    }
}
