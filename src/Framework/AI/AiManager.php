<?php

namespace Lightpack\AI;

use Lightpack\Manager\BaseManager;
use Lightpack\Container\Container;
use Lightpack\AI\Providers\Groq;
use Lightpack\AI\Providers\OpenAI;
use Lightpack\AI\Providers\Mistral;
use Lightpack\AI\Providers\Anthropic;
use Lightpack\AI\Providers\Gemini;
use Lightpack\Http\Http;

class AiManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in AI drivers
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('openai', function ($container) {
            return new OpenAI(
                new Http,
                $container->get('cache'),
                $container->get('config')
            );
        });
        
        $this->register('anthropic', function ($container) {
            return new Anthropic(
                new Http,
                $container->get('cache'),
                $container->get('config')
            );
        });
        
        $this->register('mistral', function ($container) {
            return new Mistral(
                new Http,
                $container->get('cache'),
                $container->get('config')
            );
        });
        
        $this->register('groq', function ($container) {
            return new Groq(
                new Http,
                $container->get('cache'),
                $container->get('config')
            );
        });
        
        $this->register('gemini', function ($container) {
            return new Gemini(
                new Http,
                $container->get('cache'),
                $container->get('config')
            );
        });
    }
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = $this->container->get('config')->get('ai.driver', 'openai');
        $this->setDefaultDriver($default);
    }
    
    /**
     * Get AI driver instance
     */
    public function driver(?string $name = null): AI
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "AI driver not found: {$name}";
    }
}
