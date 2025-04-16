<?php

namespace Lightpack\Jobs;

use Lightpack\Jobs\Engines\NullEngine;
use Lightpack\Jobs\Engines\SyncEngine;
use Lightpack\Jobs\Engines\RedisEngine;
use Lightpack\Jobs\Engines\DatabaseEngine;

class Connection
{
    protected static $engine;

    public static function getJobEngine(): BaseEngine
    {
        if(!self::$engine) {
            self::setJobEngine();
        }

        return self::$engine;
    }

    private static function setJobEngine()
    {
        $engineType = get_env('JOB_ENGINE', 'sync');

        switch ($engineType) {
            case 'null':
                return self::$engine = new NullEngine;
            case 'sync':
                return self::$engine = new SyncEngine;
            case 'database':
                return self::$engine = new DatabaseEngine;
            case 'redis':
                $redis = app('redis');
                $prefix = get_env('REDIS_JOB_PREFIX', 'jobs:');
                return self::$engine = new RedisEngine($redis, $prefix);
            default:
                fputs(STDERR, "Unsupported job engine type: {$engineType}");
                exit(1);
        }
    }
}