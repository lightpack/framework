<?php

namespace Lightpack\Database\Adapters;

use Lightpack\Database\DB;

class Mysql extends DB
{
    public function __construct(array $args)
    {
        // Support connecting without a database (for database creation)
        $dsn = "mysql:host={$args['host']};port={$args['port']}";
        
        if (isset($args['database']) && $args['database'] !== null) {
            $dsn .= ";dbname={$args['database']}";
        }
        
        parent::__construct($dsn, $args['username'], $args['password'], $args['options']);
    }
}