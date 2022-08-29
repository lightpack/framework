<?php

class E
{
    public $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }
}