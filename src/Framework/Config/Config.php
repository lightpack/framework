<?php

namespace Lightpack\Config;

class Config
{
    protected $config = [];

    public function __construct()
    {
        $configs = glob(DIR_CONFIG . '/*.php');

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
        return include $file;
    }
}
