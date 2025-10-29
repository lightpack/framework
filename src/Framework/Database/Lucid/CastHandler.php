<?php

namespace Lightpack\Database\Lucid;

use DateTimeInterface;
use InvalidArgumentException;
use Lightpack\Database\Lucid\Casts\CastInterface;

class CastHandler
{
    /**
     * Cast value to specified type.
     * 
     * Supported types:
     * - int: Cast to integer
     * - float: Cast to float
     * - string: Cast to string
     * - bool: Cast to boolean
     * - array: Cast to array (stores as JSON in database)
     * - date: Cast to DateTime (Y-m-d format)
     * - datetime: Cast to DateTime (Y-m-d H:i:s format)
     * - timestamp: Cast to Unix timestamp
     * - Custom cast classes implementing CastInterface
     */
    public function cast(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        // Check if it's a custom cast class
        if ($this->isCustomCast($type)) {
            $caster = new $type();
            return $caster->get($value);
        }

        return match($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => (bool) $value,
            'array' => $this->castToArray($value),
            'date' => $this->castToDate($value),
            'datetime' => $this->castToDateTime($value),
            'timestamp' => $this->castToTimestamp($value),
            default => throw new \InvalidArgumentException("Unknown cast type: '{$type}'. Supported types: int, float, string, bool, array, date, datetime, timestamp")
        };
    }

    /**
     * Cast value from specified type back to database format.
     */
    public function uncast(mixed $value, string $type): mixed
    {
        // Check if it's a custom cast class
        if ($this->isCustomCast($type)) {
            $caster = new $type();
            return $caster->set($value);
        }

        return match($type) {
            'int', 'float', 'string' => $value,
            'bool' => $value ? 1 : 0, // Convert boolean to int for database storage
            'array' => $this->uncastFromArray($value),
            'date' => $this->uncastFromDate($value),
            'datetime' => $this->uncastFromDateTime($value),
            'timestamp' => $this->uncastFromTimestamp($value),
            default => throw new \InvalidArgumentException("Unknown cast type: '{$type}'. Supported types: int, float, string, bool, array, date, datetime, timestamp")
        };
    }

    /**
     * Cast value to array/json.
     */
    protected function castToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        throw new InvalidArgumentException('Cannot cast value to array/json');
    }

    /**
     * Cast value to date.
     */
    protected function castToDate(mixed $value): DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (is_string($value)) {
            $date = date_create($value);
            if ($date) {
                return $date;
            }
        }

        throw new InvalidArgumentException('Cannot cast value to date');
    }

    /**
     * Cast value to datetime.
     */
    protected function castToDateTime(mixed $value): DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (is_string($value)) {
            $date = date_create($value);
            if ($date) {
                return $date;
            }
        }

        throw new InvalidArgumentException('Cannot cast value to datetime');
    }

    /**
     * Cast value to timestamp.
     */
    protected function castToTimestamp(mixed $value): int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_string($value)) {
            // Handle @ prefix for Unix timestamp strings
            if (str_starts_with($value, '@')) {
                return (int) substr($value, 1);
            }
            
            // If the string is numeric, treat it as a Unix timestamp
            if (is_numeric($value)) {
                return (int) $value;
            }
            
            // Otherwise try to parse it as a date string
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return $timestamp;
            }
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException('Cannot cast value to timestamp');
    }

    /**
     * Uncast from array/json to string.
     */
    protected function uncastFromArray(mixed $value): string
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Value must be an array');
        }

        return json_encode($value);
    }

    /**
     * Uncast from date to string.
     */
    protected function uncastFromDate(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }

    /**
     * Uncast from datetime to string.
     */
    protected function uncastFromDateTime(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    /**
     * Uncast from timestamp to string.
     */
    protected function uncastFromTimestamp(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return (string) $value->getTimestamp();
        }

        return (string) $value;
    }

    /**
     * Check if the given type is a custom cast class.
     */
    protected function isCustomCast(string $type): bool
    {
        return class_exists($type) && is_subclass_of($type, CastInterface::class);
    }
}
