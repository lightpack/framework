<?php
namespace Lightpack\Providers;

use Lightpack\Pdf\Pdf;
use Lightpack\Container\Container;
use Lightpack\Pdf\Driver\DompdfDriver;

class PdfProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('pdf', function($container) {
            $config = $container->get('config')->get('pdf');
            $driverType = $config['driver'] ?? 'dompdf';
            $options = $config[$driverType] ?? [];
            $template = $container->get('template');

            $driver = match ($driverType) {
                'dompdf' => new DompdfDriver($options),
                default  => throw new \Exception("Unknown PDF driver: {$driverType}"),
            };

            return new Pdf($driver, $template, $options);
        });

        $container->alias(Pdf::class, 'pdf');
    }
}
