<?php

namespace Lightpack\AI;

use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\Logger\Logger;

abstract class BaseProvider implements ProviderInterface
{
    public function __construct(
        protected Http $http,
        protected Cache $cache,
        protected Config $config,
        protected Logger $logger,
    ) {}

    protected function makeApiRequest(string $endpoint, array $body, array $headers = [], int $timeout = 10)
    {
        try {
            $response = $this->http
                ->headers($headers)
                ->timeout($timeout)
                ->post($endpoint, $body);

            if ($response->failed()) {
                $errorMsg = $response->error() ?: 'HTTP error ' . $response->status();
                throw new \Exception(static::class . ' API error: ' . $errorMsg);
            }

            return json_decode($response->body(), true);
        } catch (\Exception $e) {
            $this->logger->error(static::class . ' API error: ' . $e->getMessage());
            throw $e;
        }
    }
}
