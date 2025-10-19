<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class ExistsRule
{
    use ValidationMessageTrait;

    private array $columns;

    public function __construct(
        private string $table,
        string|array $columns,
        private array $where = []
    ) {
        $this->columns = (array) $columns;
        $this->message = "The selected value does not exist";
    }

    public function __invoke($value, array $data = []): bool
    {
        // Skip validation for empty values (use required() for that)
        if (empty($value) && $value !== '0' && $value !== 0) {
            return true;
        }

        $query = db()->table($this->table);

        // For single column, check the value directly
        if (count($this->columns) === 1) {
            $query->where($this->columns[0], '=', $value);
        } else {
            // For multiple columns, check composite uniqueness
            foreach ($this->columns as $column) {
                $columnValue = isset($data[$column]) ? $data[$column] : $value;
                $query->where($column, '=', $columnValue);
            }
        }

        // Add additional WHERE conditions
        foreach ($this->where as $column => $whereValue) {
            $query->where($column, '=', $whereValue);
        }

        return $query->count() > 0;
    }
}
