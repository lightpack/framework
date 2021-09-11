<?php

namespace Lightpack\Console\Views;

class ProviderView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace App\Providers;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;

class __PROVIDER_NAME__ implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->__PROVIDER_BINDING__('__PROVIDER_ALIAS__', function ($container) {
            // 
        });
    }
}
TEMPLATE;
    }
}
