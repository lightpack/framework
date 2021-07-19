<?php

namespace Lightpack\Debug;

class Dumper
{
    public function varDump(...$args)
    {
        $this->dump('var_dump', $args);
    }

    public function printDump(...$args)
    {
        $this->dump('print_r', $args);
    }

    private function dump($dumpFunction, $args)
    {
        if (!get_env('APP_DEBUG', true)) {
            return;
        }

        $template = __DIR__ . '/templates/http/dumper.php';

        $this->render($template, [
            'args' => $args[0],
            'dump_function' => $dumpFunction,
        ]);
    }

    private function render($template, $data)
    {
        // Clean the output buffer first.
        while (\ob_get_level() !== 0) {
            \ob_end_clean();
        }

        extract($data);
        ob_start();
        require $template;
        echo ob_get_clean();
        \flush();
        exit();
    }
}
