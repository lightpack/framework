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
                $this->logger->error(static::class . ' API response: ' . $response->body());
                throw new \Exception(static::class . ' API error: ' . $errorMsg);
            }

            return json_decode($response->body(), true);
        } catch (\Exception $e) {
            $this->logger->error(static::class . ' API error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a robust, order-independent cache key from selected params.
     * @param array $params The input parameters to consider
     * @param array $fields The list of keys to include in the cache key
     * @return string
     */
    protected function generateCacheKey(array $params): string
    {
        $data = [];
        $fields = ['model', 'messages', 'temperature', 'max_tokens', 'system'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                $data[$field] = $params[$field];
            }
        }
        ksort($data);
        return md5(json_encode($data));
    }
}

