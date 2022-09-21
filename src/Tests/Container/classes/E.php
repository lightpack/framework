<?php

class E
{
    public $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function foo(InterfaceFoo $foo, $bar, $baz)
    {
        return [
            'foo' => $foo::class,
            'bar' => $bar,
            'baz' => $baz,
        ];
    }
}
