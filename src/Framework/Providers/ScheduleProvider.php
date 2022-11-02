<?php

namespace Lightpack\Providers;

use Lightpack\Schedule\Schedule;
use Lightpack\Container\Container;

class ScheduleProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->instance('schedule', new Schedule());
    }
}
