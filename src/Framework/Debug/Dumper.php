<?php

namespace Lightpack\Debug;

class Dumper
{
    public function varDump(...$args)
    {
        $this->dump(function ($data) {
            return var_dump($data);
        }, ...$args);
    }

    public function printDump(...$args)
    {
        $this->dump(function ($data) {
            return print_r($data, 1);
        }, ...$args);
    }

    private function dump(callable $cb, ...$args)
    {
        if (!get_env('APP_DEBUG', true)) {
            return;
        }

        foreach ($args as $arg) {
            echo '<pre>', $cb($arg), '</pre>';
        }

        die;
    }
}
