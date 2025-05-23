<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;
use Lightpack\AI\Providers\OpenAI;
use Lightpack\AI\Providers\Anthropic;
use Lightpack\AI\Providers\Mistral;
use Lightpack\AI\Providers\Groq;
use Lightpack\AI\ProviderInterface as AIProviderInterface;

class AiProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('ai', function ($container) {
            $config = $container->get('config');
            $type = $config->get('ai.driver');

            $deps = [
                $container->get('http'),
                $container->get('cache'),
                $container->get('config'),
                $container->get('logger'),
            ];

            return match ($type) {
                'openai'    => new OpenAI(...$deps),
                'anthropic' => new Anthropic(...$deps),
                'mistral'   => new Mistral(...$deps),
                'groq'      => new Groq(...$deps),
                default     => throw new \Exception("Unknown AI driver: {$type}"),
            };
        });

        $container->alias(AIProviderInterface::class, 'ai');
    }
}
