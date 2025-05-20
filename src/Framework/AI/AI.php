<?php
namespace Lightpack\AI;

use Lightpack\Config\Config;
use Lightpack\Http\Http;
use Lightpack\Logger\Logger;
use Lightpack\Cache\Cache;

class AI
{
    protected $provider;

    public function __construct(Config $config, Http $http, Logger $logger, Cache $cache)
    {
        $default = $config->get('ai.default', 'openai');
        switch ($default) {
            case 'openai':
            default:
                $this->provider = new OpenAIProvider($config, $http, $logger, $cache);
                break;
            // Add more providers here
        }
    }

    public function generate(array $params)
    {
        return $this->provider->generate($params);
    }
}
