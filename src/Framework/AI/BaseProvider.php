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
}
