<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class DbUniqueRule
{
    use ValidationMessageTrait;

    private array $columns;

    public function __construct(
        private string $table,
        string|array $columns,
        private int|string|null $ignoreId = null,
        private string $idColumn = 'id'
    ) {
        $this->columns = (array) $columns;
        $this->message = $this->buildMessage();
    }

    public function __invoke($value, array $data = []): bool
    {
        $query = db()->table($this->table);

        // Add WHERE conditions for all columns
        foreach ($this->columns as $column) {
            // Use the field value being validated, or get from data
            $columnValue = isset($data[$column]) ? $data[$column] : $value;
            $query->where($column, '=', $columnValue);
        }

        // Ignore specific ID (for updates)
        if ($this->ignoreId !== null) {
            $query->where($this->idColumn, '!=', $this->ignoreId);
        }

        return $query->count() === 0;
    }

    private function buildMessage(): string
    {
        if (count($this->columns) === 1) {
            return "The {$this->columns[0]} has already been taken";
        }

        $fields = implode(', ', $this->columns);
        return "The combination of {$fields} has already been taken";
    }
}
