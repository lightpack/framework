<?php

namespace Lightpack\Database\Schema;

class IdentifierEscaper
{
    public static function escape(string $identifier): string
    {
        // If already backticked, return as is
        if (str_starts_with($identifier, '`') && str_ends_with($identifier, '`')) {
            return $identifier;
        }
        return '`' . str_replace('`', '', $identifier) . '`';
    }
}
