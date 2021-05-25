<?php

namespace Lightpack\Config;

use Lightpack\Exceptions\ConfigFileNotFoundException;

class Config
{
    public function __construct(array $configs = [])
    {
        foreach ($configs as $config) {
            $config = str_replace('-', '_', $config);
            $configData[$config] = $this->loadConfig($config);
        }

        foreach ($configData as $key => $value) {
            $this->{$key} = $value;
        }
    }

    private function loadConfig($file)
    {
        $filePath = DIR_CONFIG . '/' . $file . '.php';

        if (!file_exists($filePath)) {
            throw new ConfigFileNotFoundException('Could not load config file path: ' . $filePath);
        }

        return include_once $filePath;
    }
}
