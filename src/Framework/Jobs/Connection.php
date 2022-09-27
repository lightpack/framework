<?php

namespace Lightpack\Jobs;

use Lightpack\Jobs\Engines\NullEngine;
use Lightpack\Jobs\Engines\SyncEngine;
use Lightpack\Jobs\Engines\DatabaseEngine;

class Connection
{
    public static function getJobEngine(): BaseEngine
    {
        $engineType = get_env('JOB_ENGINE', 'sync');

        switch ($engineType) {
            case 'null':
                return new NullEngine;
            case 'sync':
                return new SyncEngine;
            case 'database':
                return new DatabaseEngine;
            default:
                fputs(STDERR, "Unsupported job engine type: {$engineType}");
                exit(1);
        }
    }
}