<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Mail\MailManager;
use Lightpack\Mail\Drivers\SmtpDriver;
use Lightpack\Mail\Drivers\ArrayDriver;
use Lightpack\Mail\Drivers\LogDriver;

class MailProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('mail', function ($container) {
            $manager = new MailManager();

            // Register built-in drivers
            $manager->registerDriver('smtp', new SmtpDriver());
            $manager->registerDriver('array', new ArrayDriver());
            $manager->registerDriver('log', new LogDriver());

            // Set default driver from config
            $manager->setDefaultDriver(get_env('MAIL_DRIVER', 'smtp'));

            return $manager;
        });

        $container->alias(MailManager::class, 'mail');
    }
}
