<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\DB;
use Lightpack\Database\Schema\Compilers\AddColumn;
use Lightpack\Database\Schema\Compilers\AlterTable;
use Lightpack\Database\Schema\Compilers\DropColumn;
use Lightpack\Database\Schema\Compilers\IndexKey;
use Lightpack\Database\Schema\Compilers\ModifyColumn;
use Lightpack\Database\Schema\Compilers\RenameColumn;
use Lightpack\Utils\Str;

class Table
{
    private const CONTEXT_CREATE = 'create';
    private const CONTEXT_ALTER = 'alter';
    private string $context = self::CONTEXT_CREATE;
    private string $tableName;
    private ColumnCollection $tableColumns;
    private ForeignKeyCollection $tableKeys;
    private DB $connection;
    private array $indexes = [];
    private string $engine = 'InnoDB';
    private string $charset = 'utf8mb4';
    private string $collation = 'utf8mb4_unicode_ci';

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

        $column->type('DATETIME')->nullable()->attribute('ON UPDATE CURRENT_TIMESTAMP');

        $this->tableColumns->add($column);

        return $column;
    }

    public function deletedAt(): Column
    {
        $column = new Column('deleted_at');

        $column->type('DATETIME')->nullable();

        $this->tableColumns->add($column);

        return $column;
    }


    /**
     * Add an INT column.
     */
    public function int(string $name, int $length = 11): Column
    {
        $column = new Column($name);
        $column->type('INT')->length($length);
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a BIGINT column.
     */
    public function bigint(string $name): Column
    {
        $column = new Column($name);
        $column->type('BIGINT');
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a SMALLINT column.
     */
    public function smallint(string $name): Column
    {
        $column = new Column($name);
        $column->type('SMALLINT');
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a TINYINT column.
     */
    public function tinyint(string $name): Column
    {
        $column = new Column($name);
        $column->type('TINYINT');
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a DATE column.
     */
    public function date(string $name): Column
    {
        $column = new Column($name);
        $column->type('DATE');
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a TIME column.
     */
    public function time(string $name): Column
    {
        $column = new Column($name);
        $column->type('TIME');
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a TIMESTAMP column.
     */
    public function timestamp(string $name): Column
    {
        $column = new Column($name);
        $column->type('TIMESTAMP');
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a YEAR column (MySQL-specific).
     */
    public function year(string $name): Column
    {
        $column = new Column($name);
        $column->type('YEAR');
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a JSON column.
     */
    public function json(string $name): Column
    {
        $column = new Column($name);
        $column->type('JSON');
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a CHAR column (fixed-length string).
     *
     * CHAR columns store strings of a fixed length. If the value is shorter than the specified length,
     * it is padded with spaces. This is different from VARCHAR, which stores variable-length strings
     * and uses only as much space as needed for the value.
     *
     * Use CHAR for values that are always the same length (e.g., country codes, status flags, fixed-format IDs)
     * for optimal storage and performance. For variable-length strings, use VARCHAR instead.
     *
     * @param string $name   The column name.
     * @param int    $length The fixed length of the CHAR column (default 255).
     * @return Column
     */
    public function char(string $name, int $length = 255): Column
    {
        $column = new Column($name);
        $column->type('CHAR')->length($length);
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a TINYTEXT column.
     */
    public function tinytext(string $name): Column
    {
        $column = new Column($name);
        $column->type('TINYTEXT');
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a MEDIUMTEXT column.
     */
    public function mediumtext(string $name): Column
    {
        $column = new Column($name);
        $column->type('MEDIUMTEXT');
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a LONGTEXT column.
     */
    public function longtext(string $name): Column
    {
        $column = new Column($name);
        $column->type('LONGTEXT');
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add an IP address column (VARCHAR(45) for IPv4/IPv6).
     */
    public function ipAddress(string $name = 'ip_address'): Column
    {
        $column = new Column($name);
        $column->type('VARCHAR')->length(45);
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add a MAC address column (VARCHAR(17)).
     */
    public function macAddress(string $name = 'mac_address'): Column
    {
        $column = new Column($name);
        $column->type('VARCHAR')->length(17);
        $this->tableColumns->add($column);
        return $column;
    }

    /**
     * Add morphs columns for polymorphic relations: {name}_id (BIGINT UNSIGNED), {name}_type (VARCHAR(255)).
     */
    public function morphs(string $name): array
    {
        $idColumn = new Column($name . '_id');
        $idColumn->type('BIGINT')->attribute('UNSIGNED');
        $this->tableColumns->add($idColumn);

        $typeColumn = new Column($name . '_type');
        $typeColumn->type('VARCHAR')->length(255);
        $this->tableColumns->add($typeColumn);

        return [$idColumn, $typeColumn];
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

    /**
     * @todo: Do we need this method?
     */
    private function parent(string $parentTable): ForeignKey
    {
        $key = (new Str)->foreignKey($parentTable);

        $foreign = new ForeignKey($key);

        $foreign->references('id')->on($parentTable);

        $this->tableKeys->add($foreign);

        return $foreign;
    }

    public function foreignKey(string $column): ForeignKey
    {
        // Extract _id from the column name
        $parentTable = substr($column, 0, -3);
        $parentTable = (new Str)->tableize($parentTable);

        $foreign = new ForeignKey($column);
        $foreign->references('id')->on($parentTable);

        $this->tableKeys->add($foreign);

        return $foreign;
    }

    /**
     * Add one or more columns to the table.
     */
    public function add(callable $callback): void
    {
        $callback($this);

        $sql = (new AddColumn)->compile($this);

        $this->connection->query($sql);
    }

    /**
     * Modify one or more columns in a table.
     */
    public function modify(callable $callback): void
    {
        $callback($this);

        $sql = (new ModifyColumn)->compile($this);

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
     * Rename a column.
     */
    public function renameColumn(string $oldName, string $newName): void
    {
        $sql = (new RenameColumn)->compile($this->getName(), $oldName, $newName);

        $this->connection->query($sql);
    }

    public function primary(string|array $columns): void
    {
        if($this->altering()) {
            $this->addPrimaryIndex($columns);
        } else {
            $this->indexes[] = (new IndexKey)->compile($columns, 'PRIMARY');
        }
    }

    /**
     * Drop primary key from the table.
     * 
     * Note: Dropping the primary key does not remove the column from the table but only the primary key constraint.
     * 
     * Remember that there can be only one auto column and it must be defined as a key. So if the primary key is defined as auto, 
     * you will need to remove the auto attribute first.
     * 
     * Also do not forget to drop or alter foreign keys referencing the primary key.
     */
    public function dropPrimary(): void
    {
        $sql = (new AlterTable)->compileDropPrimary($this->getName());

        $this->connection->query($sql);
    }

    /**
     * Add unique index to one or more columns.
     * 
     * NOTE: You should remove duplicate values from the columns before 
     * adding unique index otherwise it may result in "mysql error 1062".
     */
    public function unique(string|array $columns, ?string $indexName = null): void
    {
        if($this->altering()) {
            $this->addUniqueIndex($columns, $indexName);
        } else {
            $this->indexes[] = (new IndexKey)->compile($columns, 'UNIQUE', $indexName);
        }
    }

    public function dropUnique(string $indexName): void
    {
        $sql = (new AlterTable)->compileDropUnique($this->getName(), $indexName);

        $this->connection->query($sql);
    }

    public function index(string|array $columns, ?string $indexName = null): void
    {
        if($this->altering()) {
            $this->addIndex($columns, $indexName);
        } else {
            $this->indexes[] = (new IndexKey)->compile($columns, 'INDEX', $indexName);
        }
    }

    public function dropIndex(string $indexName): void
    {
        $sql = (new AlterTable)->compileDropIndex($this->getName(), $indexName);

        $this->connection->query($sql);
    }

    public function fulltext(string|array $columns, ?string $indexName = null): void
    {
        if($this->altering()) {
            $this->addFulltextIndex($columns, $indexName);
        } else {
            $this->indexes[] = (new IndexKey)->compile($columns, 'FULLTEXT', $indexName);
        }
    }

    public function dropFulltext(string ...$indexName): void
    {
        $sql = (new AlterTable)->compileDropFulltext($this->getName(), ...$indexName);

        $this->connection->query($sql);
    }

    public function spatial(string|array $columns, ?string $indexName = null): void
    {
        if($this->altering()) {
            $this->addSpatialIndex($columns, $indexName);
        } else {
            $this->indexes[] = (new IndexKey)->compile($columns, 'SPATIAL', $indexName);
        }
    }

    public function dropSpatial(string $indexName): void
    {
        $sql = (new AlterTable)->compileDropSpatial($this->getName(), $indexName);

        $this->connection->query($sql);
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function engine(string $engine): self
    {
        $this->engine = $engine;

        return $this;
    }

    public function getEngine(): string
    {
        return $this->engine;
    }

    public function charset(string $charset): self
    {
        $this->charset = $charset;

        return $this;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function collation(string $collation): self
    {
        $this->collation = $collation;

        return $this;
    }

    public function getCollation(): string
    {
        return $this->collation;
    }

    /**
     * Set context to 'create' mode which will help to determine if current 
     * table column, index or key is being added.
     */
    public function createContext(): self
    {
        $this->context = self::CONTEXT_CREATE;

        return $this;
    }

    /**
     * Set context to 'create' mode which will help to determine if current 
     * table column, index or key is being modified.
     */
    public function alterContext(): self
    {
        $this->context = self::CONTEXT_ALTER;

        return $this;
    }

    public function dropForeign(string ...$constraintName): void
    {
        $sql = (new AlterTable)->compileDropForeignKey($this->getName(), ...$constraintName);

        $this->connection->query($sql);
    }

    private function creating(): bool
    {
        return $this->context === self::CONTEXT_CREATE;
    }

    private function altering(): bool
    {
        return $this->context === self::CONTEXT_ALTER;
    }

    private function addPrimaryIndex(string|array $columns): void
    {
        $sql = (new AlterTable)->compilePrimary($this->getName(), $columns);

        $this->connection->query($sql);
    }

    private function addUniqueIndex(string|array $columns, ?string $indexName = null): void
    {

        $sql = (new AlterTable)->compileUnique($this->getName(), $columns, $indexName);

        $this->connection->query($sql);
    }

    private function addIndex(string|array $columns, ?string $indexName = null): void
    {
        $sql = (new AlterTable)->compileIndex($this->getName(), $columns, $indexName);

        $this->connection->query($sql);
    }

    private function addFulltextIndex(string|array $columns, ?string $indexName = null): void
    {
        $sql = (new AlterTable)->compileFulltext($this->getName(), $columns, $indexName);

        $this->connection->query($sql);
    }

    private function addSpatialIndex(string|array $columns, ?string $indexName = null): void
    {
        $sql = (new AlterTable)->compileSpatial($this->getName(), $columns, $indexName);

        $this->connection->query($sql);
    }
}
