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

    public function nullable(bool $columnIsNullable = true): self
    {
        $this->columnIsNullable = $columnIsNullable;

        return $this;
    }

    public function unique(?string $indexName = null): self
    {
        $this->columnIndexType = self::INDEX_UNIQUE;

        if ($indexName) {
            $this->columnIndexName = $indexName;
        } else {
            $this->columnIndexName = $this->columnName . '_unique';
        }

        return $this;
    }

    public function primary(): self
    {
        $this->columnIndexType = self::INDEX_PRIMARY;


        return $this;
    }

    public function index(?string $indexName = null): self
    {
        $this->columnIndexType = self::INDEX_INDEX;

        if ($indexName) {
            $this->columnIndexName = $indexName;
        } else {
            $this->columnIndexName = $this->columnName . '_index';
        }

        return $this;
    }

    public function increments(): self
    {
        $this->primary();
        $this->columnIncrements = true;

        return $this;
    }

    public function fulltext(?string $indexName = null): self
    {
        $this->columnIndexType = self::INDEX_FULLTEXT;

        if ($indexName) {
            $this->columnIndexName = $indexName;
        } else {
            $this->columnIndexName = $this->columnName . '_fulltext';
        }

        return $this;
    }

    public function attribute(string $columnAttribute): self
    {
        $this->columnAttribute = strtoupper($columnAttribute);

        return $this;
    }

    public function default(bool|string $value): self
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
            $escapedIndexName = IdentifierEscaper::escape($this->columnIndexName);
            $index .= " $escapedIndexName";
        }

        $escapedColumnName = IdentifierEscaper::escape($this->columnName);
        $index .= " ($escapedColumnName)";
        
        return $index;
    }

    public function compileColumn()
    {
        $column = IdentifierEscaper::escape($this->columnName) . " {$this->columnType}";

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
            if(is_bool($this->columnDefaultValue)) {
                $column .= " DEFAULT " . ($this->columnDefaultValue ? '1' : '0');
            } else {
                if ($this->columnDefaultValue !== 'NULL' && $this->columnDefaultValue !== 'CURRENT_TIMESTAMP') {
                    $default = "'{$this->columnDefaultValue}'";
                } else {
                    $default = "{$this->columnDefaultValue}";
                }
    
                $column .= " DEFAULT {$default}";
            }
        }

        return $column;
    }

    public function getName()
    {
        return $this->columnName;
    }
}
