<?php

namespace Lightpack\Mail;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class MailProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('mail', function ($container) {
            return new MailManager($container);
        });

        $container->alias(MailManager::class, 'mail');
    }
}
