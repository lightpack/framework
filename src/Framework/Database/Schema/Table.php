<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\Schema\Compilers\AddColumn;
use Lightpack\Database\Schema\Compilers\DropColumn;
use Lightpack\Database\Schema\Compilers\ModifyColumn;
use Lightpack\Utils\Str;

class Table
{
    private $tableName;
    private $renameColumns = [];

    /**
     * @var ColumnCollection
     */
    private $tableColumns;

    /**
     * @var ForeignKeyCollection
     */
    private $tableKeys;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
        $this->tableColumns = new ColumnCollection();
        $this->tableKeys = new ForeignKeyCollection();
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

    public function renameColumn(string $oldName, string $newName): void
    {
        $this->renameColumns[$oldName] = $newName;
    }

    public function getRenameColumns(): array
    {
        return $this->renameColumns;
    }

    public function id(string $name = 'id'): Column
    {
        $column = new Column($name);

        $column->type('BIGINT')->attribute('UNSIGNED')->increments()->index(Column::INDEX_PRIMARY);

        $this->tableColumns->add($column);

        return $column;
    }

    public function string(string $name, int $length = 255): Column
    {
        $column = new Column($name);

        $column->type('VARCHAR');
        $column->length($length);

        $this->tableColumns->add($column);

        return $column;
    }

    public function boolean(string $column, bool $default = false): Column
    {
        $column = new Column($column);

        $column->type('BOOLEAN');
        $column->default($default);

        $this->tableColumns->add($column);

        return $column;
    }

    public function decimal(string $column, int $precision = 8, int $scale = 2): Column
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

    public function created_at(): Column
    {
        $column = new Column('created_at');

        $column->type('DATETIME');
        $column->default('CURRENT_TIMESTAMP');

        $this->tableColumns->add($column);

        return $column;
    }

    public function updated_at(): Column
    {
        $column = new Column('updated_at');

        $column->type('DATETIME');
        $column->attribute('ON UPDATE CURRENT_TIMESTAMP');

        $this->tableColumns->add($column);

        return $column;
    }

    public function deleted_at(): Column
    {
        $column = new Column('deleted_at');

        $column->type('DATETIME');
        $column->nullable();

        $this->tableColumns->add($column);

        return $column;
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
        $parentTable = (new Str)->tableize($column);

        $foreign = new ForeignKey($column);

        $foreign->references('id')->on($parentTable);

        $this->tableKeys->add($foreign);

        return $foreign;
    }

    /**
     * Alter table add columns.
     *
     * @param Table $table
     * @return void
     */
    public function addColumn(): string
    {
        $sql = (new AddColumn)->compile($this);

        return $sql;
    }

    /**
     * Drop columns in a table.
     *
     * @param string $table
     * @param string ...$columns
     * @return void
     */
    public function dropColumn(string ...$columns): string
    {
        $sql = (new DropColumn)->compile($this->getName(), ...$columns);

        return $sql;
    }

    /**
     * Modify a column.
     *
     * @param Table $table
     * @return void
     */
    public function modifyColumn(): string
    {
        $sql = (new ModifyColumn)->compile($this);

        return $sql;
    }

    /**
     * Set a string column automagically.
     * 
     * For example: 
     * $table->email(125); // Sets the column type to VARCHAR and the column length to 125.
     */
    public function __call($name, $arguments): Column
    {
        // if column ends with '_at', set the column type to DATETIME
        if (substr($name, -3) === '_at') {
            return $this->datetime($name);
        }

        // otherwise, set the column type to VARCHAR
        $length = $arguments[0] ?? 255;
        return $this->string($name, (int) $length);
    }
}
