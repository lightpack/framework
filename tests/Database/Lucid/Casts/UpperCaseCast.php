<?php

namespace Lightpack\Database\Lucid\Casts;

use Lightpack\Database\Lucid\Casts\CastInterface;

/**
 * Example custom cast that converts strings to uppercase.
 */
class UpperCaseCast implements CastInterface
{
    public function get(mixed $value): mixed
    {
        return strtoupper($value);
    }

    public function set(mixed $value): mixed
    {
        return strtolower($value);
    }
}
