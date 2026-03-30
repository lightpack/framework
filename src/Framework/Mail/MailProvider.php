<?php

namespace Lightpack\Mail;

use Lightpack\Support\ProviderInterface;
use Lightpack\Container\Container;
use Lightpack\Mail\MailManager;

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
