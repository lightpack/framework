<?php

namespace Lightpack\Debug;

class Dumper
{
    public function varDump(...$args)
    {
        foreach ($args as $arg) {
            echo '<pre>', var_dump($arg), '</pre>';
        }
    }

    public function printDump(...$args)
    {
        foreach ($args as $arg) {
            echo '<pre>', print_r($arg), '</pre>';
        }
    }
}
