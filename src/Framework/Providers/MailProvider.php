<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Mail\MailManager;
use Lightpack\Mail\Drivers\SmtpDriver;
use Lightpack\Mail\Drivers\ArrayDriver;
use Lightpack\Mail\Drivers\LogDriver;
use Lightpack\Mail\Drivers\ResendDriver;

class MailProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('mail', function ($container) {
            $manager = new MailManager();
            $driver = get_env('MAIL_DRIVER', 'smtp');

            match ($driver) {
                'smtp' => $manager->registerDriver('smtp', new SmtpDriver()),
                'resend' => $manager->registerDriver('resend', new ResendDriver()),
                'array' => $manager->registerDriver('array', new ArrayDriver()),
                'log' => $manager->registerDriver('log', new LogDriver()),
                default => throw new \Exception('Invalid mail driver: ' . $driver),
            };

            $manager->setDefaultDriver($driver);

            return $manager;
        });

        $container->alias(MailManager::class, 'mail');
    }
}
