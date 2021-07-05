<?php

namespace Lightpack\Config;

use Lightpack\Exceptions\ConfigFileNotFoundException;

class Config
{
    protected $config = [];

    public function __construct(array $configs = [])
    {
        foreach ($configs as $config) {
            $this->config = array_merge(
                $this->config,
                $this->loadConfig($config)
            );
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, $value)
    {
        if (!isset($this->config[$key])) {
            $this->config[$key] = $value;
        }
    }

    private function loadConfig($file): array
    {
        $filePath = DIR_CONFIG . '/' . $file . '.php';

        if (!file_exists($filePath)) {
            throw new ConfigFileNotFoundException(
                'Could not load config file path: ' . $filePath
            );
        }

        return include $filePath;
    }
}
