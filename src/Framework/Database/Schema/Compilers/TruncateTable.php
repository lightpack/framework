<?php

namespace Lightpack\Database\Schema\Compilers;

class TruncateTable
{
    public function compile(string $table): string
    {
       return "TRUNCATE {$table};";
    }
}
