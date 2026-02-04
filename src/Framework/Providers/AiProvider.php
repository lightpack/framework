<?php

namespace Lightpack\Providers;

use Lightpack\Providers\ProviderInterface;
use Lightpack\Container\Container;
use Lightpack\AI\AI;
use Lightpack\AI\Providers\Groq;
use Lightpack\AI\Providers\OpenAI;
use Lightpack\AI\Providers\Mistral;
use Lightpack\AI\Providers\Anthropic;
use Lightpack\AI\Providers\Gemini;
use Lightpack\Http\Http;

class AiProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('ai', function ($container) {
            $config = $container->get('config');
            $type = $config->get('ai.driver');

            $dependencies = [
                new Http,
                $container->get('cache'),
                $container->get('config'),
            ];

            return match ($type) {
                'openai'    => new OpenAI(...$dependencies),
                'anthropic' => new Anthropic(...$dependencies),
                'mistral'   => new Mistral(...$dependencies),
                'groq'      => new Groq(...$dependencies),
                'gemini'    => new Gemini(...$dependencies),
                default     => throw new \Exception("Unknown AI driver: {$type}"),
            };
        });

        $container->alias(AI::class, 'ai');
    }
}
