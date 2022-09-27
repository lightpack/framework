<?php

namespace Lightpack\Database\Adapters;

use Lightpack\Database\DB;

class Mysql extends DB
{
    public function __construct(array $args)
    {
        $dsn = "mysql:host={$args['host']};port={$args['port']};dbname={$args['database']}";
        parent::__construct($dsn, $args['username'], $args['password'], $args['options']);
    }
}