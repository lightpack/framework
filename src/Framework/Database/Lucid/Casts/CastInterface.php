<?php

namespace Lightpack\Database\Lucid\Casts;

/**
 * Interface for custom attribute casting.
 * 
 * Implement this interface to create custom cast types for model attributes.
 * 
 * Example:
 * ```php
 * class MoneyCast implements CastInterface
 * {
 *     public function get(mixed $value): Money
 *     {
 *         return new Money($value);
 *     }
 * 
 *     public function set(mixed $value): string
 *     {
 *         return $value instanceof Money ? $value->amount() : $value;
 *     }
 * }
 * ```
 */
interface CastInterface
{
    /**
     * Transform the attribute from the database value to a PHP value.
     *
     * @param mixed $value The raw value from the database
     * @return mixed The transformed value
     */
    public function get(mixed $value): mixed;

    /**
     * Transform the attribute from a PHP value to a database value.
     *
     * @param mixed $value The PHP value to store
     * @return mixed The value to store in the database
     */
    public function set(mixed $value): mixed;
}
