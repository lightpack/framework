<?php

class D
{
    public $a;
    public $b;
    public $c;

    public function __construct(A $a, B $b, C $c)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }
}