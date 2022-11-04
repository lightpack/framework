<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\DB;
use Lightpack\Database\Schema\Compilers\AddColumn;
use Lightpack\Database\Schema\Compilers\DropColumn;
use Lightpack\Database\Schema\Compilers\ModifyColumn;
use Lightpack\Database\Schema\Compilers\RenameColumn;
use Lightpack\Utils\Str;

class Table
{
    private string $tableName;
    private ColumnCollection $tableColumns;
    private ForeignKeyCollection $tableKeys;
    private DB $connection;

    public function __construct(string $tableName, DB $connection)
    {
        $this->tableName = $tableName;
        $this->tableColumns = new ColumnCollection();
        $this->tableKeys = new ForeignKeyCollection();
        $this->connection = $connection;
    }

    public function column(string $column): Column
    {
        $column = new Column($column);

        $this->tableColumns->add($column);

        return $column;
    }

    public function columns(): ColumnCollection
    {
        return $this->tableColumns;
    }

    public function foreignKeys(): ForeignKeyCollection
    {
        return $this->tableKeys;
    }

    public function getName()
    {
        return $this->tableName;
    }

    public function id(string $name = 'id'): Column
    {
        $column = new Column($name);

        $column->type('BIGINT')->attribute('UNSIGNED')->increments();

        $this->tableColumns->add($column);

        return $column;
    }

    public function varchar(string $name, int $length = 255): Column
    {
        $column = new Column($name);

        $column->type('VARCHAR');
        $column->length($length);

        $this->tableColumns->add($column);

        return $column;
    }

    public function text(string $name): Column
    {
        $column = new Column($name);

        $column->type('TEXT');

        $this->tableColumns->add($column);

        return $column;
    }

    public function boolean(string $column, bool $default = false): Column
    {
        $column = new Column($column);

        $column->type('tinyint')->default($default ? 1 : 0);

        $this->tableColumns->add($column);

        return $column;
    }

    public function decimal(string $column, int $precision = 10, int $scale = 2): Column
    {
        $column = new Column($column);

        $column->type('DECIMAL(' . $precision . ',' . $scale . ')');

        $this->tableColumns->add($column);

        return $column;
    }

    public function enum(string $column, array $values): Column
    {
        $column = new Column($column);

        $column->enum($values);

        $this->tableColumns->add($column);

        return $column;
    }

    public function createdAt(): Column
    {
        $column = new Column('created_at');

        $column->type('DATETIME');
        $column->default('CURRENT_TIMESTAMP');

        $this->tableColumns->add($column);

        return $column;
    }

    public function updatedAt(): Column
    {
        $column = new Column('updated_at');

        $column->type('DATETIME');
        $column->attribute('ON UPDATE CURRENT_TIMESTAMP');

        $this->tableColumns->add($column);

        return $column;
    }

    public function deletedAt(): Column
    {
        $column = new Column('deleted_at');

        $column->type('DATETIME');
        $column->nullable();

        $this->tableColumns->add($column);

        return $column;
    }


    /**
     * This method will add 'created_at' and 'updated_at' columns to the table.
     */
    public function timestamps(): void
    {
        $this->createdAt();
        $this->updatedAt();
    }

    public function datetime(string $column): Column
    {
        $column = new Column($column);

        $column->type('DATETIME');
        $column->nullable();

        $this->tableColumns->add($column);

        return $column;
    }

    public function parent(string $parentTable): ForeignKey
    {
        $key = (new Str)->foreignKey($parentTable);

        $foreign = new ForeignKey($key);

        $foreign->references('id')->on($parentTable);

        $this->tableKeys->add($foreign);

        return $foreign;
    }

    public function foreignKey(string $column): ForeignKey
    {
        $parentTable = explode('_', $column)[0];
        $parentTable = (new Str)->tableize($parentTable);

        $foreign = new ForeignKey($column);
        $foreign->references('id')->on($parentTable);

        $this->tableKeys->add($foreign);

        return $foreign;
    }

    /**
     * Add one or more columns to the table.
     */
    public function addColumn(): void
    {
        $sql = (new AddColumn)->compile($this);

        $this->connection->query($sql);
    }

    /**
     * Drop one or more columns in a table.
     */
    public function dropColumn(string ...$column): void
    {
        $sql = (new DropColumn)->compile($this->getName(), ...$column);

        $this->connection->query($sql);
    }

    /**
     * Modify one or more columns in a table.
     */
    public function modifyColumn(): void
    {
        $sql = (new ModifyColumn)->compile($this);

        $this->connection->query($sql);
    }

    /**
     * Rename a column.
     */
    public function renameColumn(string $oldName, string $newName): void
    {
        $sql = (new RenameColumn)->compile($this->getName(), $oldName, $newName);

        $this->connection->query($sql);
    }
}
