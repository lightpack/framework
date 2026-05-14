<?php

namespace Lightpack\Schedule;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class ScheduleProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->instance('schedule', new Schedule);
    }
}
