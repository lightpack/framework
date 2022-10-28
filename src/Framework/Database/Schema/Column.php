<?php

namespace Lightpack\Database\Schema;

class Column
{
    protected $columnName;
    protected $columnType;
    protected $columnLength;
    protected $columnDefaultValue;
    protected $columnIsNullable = false;
    protected $columnIndexType;
    protected $columnIndexName;
    protected $columnIncrements = false;
    protected $columnAttribute;

    public const DEFAULT_NULL = 'NULL';
    public const DEFAULT_CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';
    public const ATTRIBUTE_BINARY = 'BINARY';
    public const ATTRIBUTE_UNSIGNED = 'UNSIGNED';
    public const ATTRIBUTE_UNSIGNED_ZEROFILL = 'UNSIGNED ZEROFILL';
    public const ATTRIBUTE_ON_UPDATE_CURRENT_TIMESTAMP = 'ON UPDATE CURRENT_TIMESTAMP';
    public const INDEX_PRIMARY = 'PRIMARY KEY';
    public const INDEX_UNIQUE = 'UNIQUE';
    public const INDEX_INDEX = 'INDEX';
    public const INDEX_FULLTEXT = 'FULLTEXT';
    public const INDEX_SPATIAL = 'SPATIAL';

    public function __construct(string $columnName)
    {
        $this->columnName = $columnName;
    }

    public function type(string $columnType): self
    {
        $this->columnType = strtoupper($columnType);

        return $this;
    }

    public function enum(array $values): self
    {
        $this->columnType = 'ENUM';
        
        $this->columnLength = implode(',', array_map(function ($value) {
            return "'" . $value . "'";
        }, $values));

        return $this;
    }

    public function length(int $columnLength): self
    {
        $this->columnLength = $columnLength;

        return $this;
    }

    public function nullable(): self
    {
        $this->columnIsNullable = true;

        return $this;
    }

    public function index(string $indexType, string $indexName = null): self
    {
        $this->columnIndexType = strtoupper($indexType);

        if ($indexName) {
            $this->columnIndexName = $indexName;
        }

        return $this;
    }

    public function increments(): self
    {
        $this->columnIncrements = true;

        return $this;
    }

    public function attribute(string $columnAttribute): self
    {
        $this->columnAttribute = strtoupper($columnAttribute);

        return $this;
    }

    public function default(string $value): self
    {
        $this->columnDefaultValue = $value;

        return $this;
    }

    public function compileIndex()
    {
        if (!$this->columnIndexType) {
            return null;
        }

        $index = "{$this->columnIndexType}";

        if ($this->columnIndexName) {
            $index .= " $this->columnIndexName";
        }

        $index .= " ($this->columnName)";
        
        return $index;
    }

    public function compileColumn()
    {
        $column = "{$this->columnName} {$this->columnType}";

        if ($this->columnLength) {
            $column .= "($this->columnLength)";
        }

        if ($this->columnAttribute) {
            $column .= " {$this->columnAttribute}";
        }

        if ($this->columnIncrements) {
            $column .= " AUTO_INCREMENT";
        }

        if ($this->columnIsNullable) {
            $column .= " NULL";
        } else {
            $column .= " NOT NULL";
        }

        if (isset($this->columnDefaultValue)) {
            if ($this->columnDefaultValue !== 'NULL' && $this->columnDefaultValue !== 'CURRENT_TIMESTAMP') {
                $default = "'{$this->columnDefaultValue}'";
            } else {
                $default = "{$this->columnDefaultValue}";
            }

            $column .= " DEFAULT {$default}";
        }

        return $column;
    }

    public function getName()
    {
        return $this->columnName;
    }
}
