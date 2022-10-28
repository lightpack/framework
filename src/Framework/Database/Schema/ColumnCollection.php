<?php

namespace Lightpack\Database\Schema;

class ColumnCollection
{
    /**
     * @var array Lightpack\Database\Schema\Column
     */
    private $columns = [];

    /**
     * @var string The table operation type as context.
     */
    private $context = 'create';

    public function add(Column $column)
    {
        $this->columns[] = $column;
    }

    public function context(string $context): void
    {
        $this->context = $context;
    }

    public function compile()
    {
        $columns = [];
        $indexes = [];

        foreach ($this->columns as $column) {
            $columns[$column->getName()] = $column->compileColumn();

            if($index = $column->compileIndex()) {
                $indexes[] = $index;
            }
        }

        if(in_array($this->context, ['create', 'add'])) {
            $elements = array_merge($columns, $indexes);
        } else {
            $elements = $columns;
        }


        if($this->context === 'add') {
            foreach($elements as $key => $value) {
                $elements[$key] = "ADD {$value}";
            }
        }

        if($this->context === 'change') {
            foreach($elements as $key => $value) {
                $elements[$key] = "CHANGE {$key} {$value}";
            }
        }

        return implode(', ', $elements);
    }
}
