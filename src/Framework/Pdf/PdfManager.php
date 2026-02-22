<?php

namespace Lightpack\Pdf;

use Lightpack\Manager\BaseManager;
use Lightpack\Container\Container;
use Lightpack\Pdf\Driver\DompdfDriver;

class PdfManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in PDF drivers
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('dompdf', function ($container) {
            $config = $container->get('config');
            $options = $config->get('pdf.dompdf', []);
            
            return new Pdf(
                new DompdfDriver($options),
                $container->get('template'),
                $options
            );
        });
    }
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = $this->container->get('config')->get('pdf.driver', 'dompdf');
        $this->setDefaultDriver($default);
    }
    
    /**
     * Get PDF driver instance
     */
    public function driver(?string $name = null): Pdf
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "PDF driver not found: {$name}";
    }
}
