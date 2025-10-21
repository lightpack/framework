<?php

namespace Lightpack\Config;

use Lightpack\Utils\Arr;

class Config
{
    protected Arr $arr;
    protected $config = [];

    public function __construct(?string $configDir = null)
    {
        $this->arr = new Arr;
        $configDir = $configDir ?? (defined('DIR_CONFIG') ? \DIR_CONFIG : null);
        
        if ($configDir === null) {
            throw new \RuntimeException('Config directory not specified and DIR_CONFIG constant not defined');
        }
        
        $configs = glob($configDir . '/*.php');

        foreach ($configs as $config) {
            $this->config = array_merge(
                $this->config,
                $this->loadConfig($config)
            );
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->arr->get($key, $this->config, $default);
    }

    public function set(string $key, $value)
    {
        if(!$this->arr->has($key, $this->config)) {
            $this->arr->set($key, $value, $this->config);
        }
    }

    private function loadConfig($file): array
    {
        return include $file;
    }
}
