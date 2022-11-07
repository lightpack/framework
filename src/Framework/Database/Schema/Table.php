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
     * Add unique index to one or more columns.
     * 
     * NOTE: You should remove duplicate values from the columns before 
     * adding unique index otherwise it may result in "mysql error 1062".
     */
    public function unique(string|array $columns, string $indexName = null): void
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

    public function index(string|array $columns, string $indexName = null): void
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

    public function fulltext(string|array $columns, string $indexName = null): void
    {
        if($this->altering()) {
            $this->addFulltextIndex($columns, $indexName);
        } else {
            $this->indexes[] = (new IndexKey)->compile($columns, 'FULLTEXT', $indexName);
        }
    }

    public function dropFulltext(string $indexName): void
    {
        $sql = (new AlterTable)->compileDropFulltext($this->getName(), $indexName);

        $this->connection->query($sql);
    }

    public function spatial(string|array $columns, string $indexName = null): void
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

    private function addUniqueIndex(string|array $columns, string $indexName = null): void
    {

        $sql = (new AlterTable)->compileUnique($this->getName(), $columns, $indexName);

        $this->connection->query($sql);
    }

    private function addIndex(string|array $columns, string $indexName = null): void
    {
        $sql = (new AlterTable)->compileIndex($this->getName(), $columns, $indexName);

        $this->connection->query($sql);
    }

    private function addFulltextIndex(string|array $columns, string $indexName = null): void
    {
        $sql = (new AlterTable)->compileFulltext($this->getName(), $columns, $indexName);

        $this->connection->query($sql);
    }

    private function addSpatialIndex(string|array $columns, string $indexName = null): void
    {
        $sql = (new AlterTable)->compileSpatial($this->getName(), $columns, $indexName);

        $this->connection->query($sql);
    }
}
