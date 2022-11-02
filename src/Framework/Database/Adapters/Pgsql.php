<?php

namespace Lightpack\Database\Adapters;

use Lightpack\Database\DB;

class Pgsql extends DB
{
    public function __construct(array $args)
    {
        $dsn = "pgsql:host={$args['host']};port={$args['port']};dbname={$args['database']}";
        parent::__construct($dsn, $args['username'], $args['password'], $args['options']);
    }
}