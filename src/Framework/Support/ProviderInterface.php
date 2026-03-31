<?php

namespace Lightpack\Support;

use Lightpack\Container\Container;

interface ProviderInterface
{
    public function register(Container $container);
}
