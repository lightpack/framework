<?php

namespace Lightpack\Jobs;

use Lightpack\Jobs\Engines\DatabaseEngine;

class Connection
{
    public static function getJobEngine(): BaseEngine
    {
        $engineType = get_env('JOB_ENGINE', 'database');

        switch ($engineType) {
            case 'database':
                return new DatabaseEngine;
            default:
                fputs(STDERR, "Unsupported job engine type: {$engineType}");
                exit(1);
        }
    }
}