<?php

namespace Lightpack\Module;

use Lightpack\Http\Request;
use Lightpack\Config\Config;

class Module
{
    private $type;
    private $name;
    private $config;
    private $request;

    public function __construct(Request $request, Config $config)
    {
        $this->config = $config;
        $this->request = $request;

        $this->setModuleType();
        $this->setModuleName();
    }

    public function isDiscovered()
    {
        return $this->name !== null;
    }

    public function getModuleName()
    {
        return $this->name;
    }

    public function getModuleType()
    {
        return $this->type;
    }

    public function getModulePath()
    {
        return DIR_MODULES . '/' . $this->name;
    }

    public function getModuleRoutesFilePath()
    {
        $routesFile = $this->getModulePath() . '/routes/' . $this->type . '.php';

        if (file_exists($routesFile)) {
            return $routesFile;
        }

        throw new \Exception('Could not find routes for module: ' . $this->name);
    }

    public function getModuleConfiguration()
    {
        $configFile = $this->getModulePath() . '/config.php';

        if (file_exists($configFile)) {
            $this->configuration = require_once($configFile);
        }

        throw new \Exception('Could not find configuration for module: ' . $this->name);
    }

    public function getActiveModules()
    {
        return $this->config->get('modules');
    }

    private function setModuleType()
    {
        $segment = $this->request->segments(0);

        $this->type = 'frontend';

        if ($segment == $this->config->get('app.admin.route.prefix')) {
            $this->type = 'backend';
        } elseif ($segment == $this->config->get('app.api.route.prefix')) {
            $this->type = 'api';
        }
    }

    private function setModuleName()
    {
        if ($this->type == 'api' || $this->type == 'backend') {
            $segment = $this->request->segments(1);
        } else {
            $segment = $this->request->segments(0);
        }

        $modules = $this->config->get('modules');
        $this->name = $modules['/' . $segment] ?? null;
    }
}
