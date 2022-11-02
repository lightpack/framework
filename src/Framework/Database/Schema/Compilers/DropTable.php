<?php

namespace Lightpack\Database\Schema\Compilers;

class DropTable
{
    public function compile(string $table): string
    {
        return "DROP TABLE IF EXISTS {$table}";
    }
}
