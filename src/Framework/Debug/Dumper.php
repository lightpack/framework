<?php

namespace Lightpack\Debug;

class Dumper
{
    public function varDump(...$args)
    {
        if (!get_env('APP_DEBUG', true)) {
            return;
        }

        foreach ($args as $arg) {
            echo '<pre>', var_dump($arg), '</pre>';
        }
    }

    public function printDump(...$args)
    {
        if (!get_env('APP_DEBUG', true)) {
            return;
        }

        foreach ($args as $arg) {
            echo '<pre>', print_r($arg), '</pre>';
        }
    }
}
