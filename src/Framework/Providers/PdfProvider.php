<?php
namespace Lightpack\Providers;

use Lightpack\Pdf\Pdf;
use Lightpack\Container\Container;
use Lightpack\Pdf\PdfManager;

class PdfProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('pdf.manager', function ($container) {
            return new PdfManager($container);
        });

        $container->register('pdf', function ($container) {
            return $container->get('pdf.manager')->driver();
        });

        $container->alias(PdfManager::class, 'pdf.manager');
        $container->alias(Pdf::class, 'pdf');
    }
}
