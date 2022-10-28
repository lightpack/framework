<?php

namespace Lightpack\Database\Schema;

class ForeignKey
{
    private $foreignKey;
    private $parentTable;
    private $parentColumn = 'id';
    private $updateAction;
    private $deleteAction;

    public const ACTION_CASCADE = 'CASCADE';
    public const ACTION_RESTRICT = 'RESTRICT';
    public const ACTION_SET_NULL = 'SET NULL';

    public function __construct(string $foreignKey = null)
    {
        $this->foreignKey = $foreignKey;
        $this->updateAction = self::ACTION_RESTRICT;
        $this->deleteAction = self::ACTION_RESTRICT;
    }

    public function references(string $parentColumn): self
    {
        $this->parentColumn = $parentColumn;

        return $this;
    }

    public function on(string $parentTable): self
    {
        $this->parentTable = $parentTable;

        return $this;
    }

    public function cascadeOnDelete(): self
    {
        $this->deleteAction = self::ACTION_CASCADE;

        return $this;
    }

    public function cascadeOnUpdate(): self
    {
        $this->updateAction = self::ACTION_CASCADE;

        return $this;
    }

    public function restrictOnDelete(): self
    {
        $this->deleteAction = self::ACTION_RESTRICT;

        return $this;
    }

    public function restrictOnUpdate(): self
    {
        $this->updateAction = self::ACTION_RESTRICT;

        return $this;
    }

    public function nullOnDelete(): self
    {
        $this->deleteAction = self::ACTION_SET_NULL;

        return $this;
    }

    public function nullOnUpdate(): self
    {
        $this->updateAction = self::ACTION_SET_NULL;

        return $this;
    }

    public function compile()
    {
        $constraint[] = "FOREIGN KEY ({$this->foreignKey})";
        $constraint[] = "REFERENCES {$this->parentTable}({$this->parentColumn})";

        if ($this->deleteAction) {
            $constraint[] = "ON DELETE {$this->deleteAction}";
        }

        if ($this->updateAction) {
            $constraint[] = "ON UPDATE {$this->updateAction}";
        }

        return implode(' ', $constraint);
    }
}
