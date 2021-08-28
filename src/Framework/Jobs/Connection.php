<?php

namespace Lightpack\Jobs;

use Lightpack\Jobs\Engines\DatabaseEngine;
use Lightpack\Jobs\Engines\BeanstalkEngine;

class Connection
{
    public static function getJobEngine(): BaseEngine
    {
        $engineType = get_env('JOB_ENGINE', 'database');

        switch ($engineType) {
            case 'database':
                return new DatabaseEngine;
                break;
            case 'beanstalk':
                return new BeanstalkEngine;
                break;
            default:
                fputs(STDERR, "Unsupported job engine type: {$engineType}");
                exit(1);
        }
    }
}