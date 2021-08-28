<?php

namespace Lightpack\Console\Commands;

use Lightpack\Jobs\Worker;
use Lightpack\Console\ICommand;

class ProcessJobs implements ICommand
{
    public function run(array $arguments = [])
    {
        $sleep = $this->parseSleepArgument($arguments);
        $sleep = $sleep ?? 5;

        $worker = new Worker(['sleep' => $sleep]);

        $worker->run();
    }

    private function parseSleepArgument($args)
    {
        if(count($args) === 0) {
            return null;
        }

        foreach($args as $arg) {
            if(strpos($arg, '--sleep') === 0) {
                $fragments = explode('=', $arg);

                if(isset($fragments[1])) {
                    return (int) $fragments[1];
                }
            }
        }
    }
}