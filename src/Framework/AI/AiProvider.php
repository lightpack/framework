<?php

namespace Lightpack\AI;

use Lightpack\Support\ProviderInterface;
use Lightpack\Container\Container;
use Lightpack\AI\AI;
use Lightpack\AI\AiManager;

class AiProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('ai.manager', function ($container) {
            return new AiManager($container);
        });

        $container->register('ai', function ($container) {
            return $container->get('ai.manager')->driver();
        });

        $container->alias(AiManager::class, 'ai.manager');
        $container->alias(AI::class, 'ai');
    }
}
