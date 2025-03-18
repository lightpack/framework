<?php

namespace Lightpack\Database\Query;

use Closure;
use Lightpack\Database\DB;
use Lightpack\Pagination\Pagination;

class Query
{
    protected $connection;
    protected $table;
    protected $bindings = [];
    protected $components = [
        'alias' => null,
        'columns' => [],
        'distinct' => false,
        'join' => [],
        'where' => [],
        'group' => [],
        'order' => [],
        'lock' => [],
        'limit' => null,
        'offset' => null,
    ];

    public function __construct($table = null, DB $connection = null)
    {
        $this->table = $table;
        $this->connection = $connection ?? app('db');
    }

    public function setConnection(DB $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function setTable(string $table)
    {
        $this->table = $table;
        return $this;
    }

    public function insert(array $data)
    {
        return $this->executeInsert($data);
    }

    public function insertIgnore(array $data)
    {
        return $this->executeInsert($data, true);
    }

    private function executeInsert(array $data, bool $shouldIgnore = false)
    {
        return $this->executeBulkInsert($data, $shouldIgnore);
    }

    private function executeBulkInsert(array $data, bool $shouldIgnore = false)
    {
        if (empty($data)) {
            return;
        }

        if(! is_array(reset($data))) {
            $data = [$data];
        }

        // Loop data to prepare for parameter binding and validate types
        foreach ($data as $row) {
            foreach ($row as $value) {
                if (!$this->isValidParameterType($value)) {
                    throw new \InvalidArgumentException(
                        'Invalid parameter type. Allowed types are: null, bool, int, float, string, DateTime'
                    );
                }
            }
            $this->bindings = array_merge($this->bindings, array_values($row));
        }

        $columns = array_keys($data[0]);
        $compiler = new Compiler($this);
        $query = $compiler->compileBulkInsert($columns, $data, $shouldIgnore);
        $result = $this->connection->query($query, $this->bindings);

        $this->resetQuery();
        return $result;
    }

    protected function isValidParameterType($value): bool
    {
        return is_null($value) 
            || is_bool($value)
            || is_int($value) 
            || is_float($value) 
            || is_string($value)
            || $value instanceof \DateTime;
    }

    public function update(array $data)
    {
        $compiler = new Compiler($this);
        $this->bindings = array_merge(array_values($data), $this->bindings);
        $query = $compiler->compileUpdate(array_keys($data));
        $result = $this->connection->query($query, $this->bindings);
        $this->resetQuery();
        return $result;
    }

    public function delete()
    {
        $compiler = new Compiler($this);
        $query = $compiler->compileDelete();
        $result = $this->connection->query($query, $this->bindings);
        $this->resetQuery();
        return $result;
    }

    public function alias(string $alias): static
    {
        $this->components['alias'] = $alias;
        return $this;
    }

    public function select(string ...$columns): static
    {
        $this->components['columns'] = $columns;
        return $this;
    }

    /**
     * Lock the fetched rows from update until the transaction is commited.
     */
    public function forUpdate(): static
    {
        $this->components['lock']['for_update'] = true;
        return $this;
    }

    /**
     * Skip any rows that are locked for update by other transactions.
     */
    public function skipLocked(): static
    {
        $this->components['lock']['skip_locked'] = true;
        return $this;
    }

    public function from(string $table, string $alias = null): static
    {
        $this->table = $table;
        $this->components['alias'] = $alias;
        return $this;
    }

    public function distinct(): static
    {
        $this->components['distinct'] = true;
        return $this;
    }

    /**
     * This method is used to conditionally build the where clause.
     * 
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed $value
     */
    public function where($column, string $operator = '=', $value = null, string $joiner = 'AND'): static
    {
        if ($column instanceof Closure) {
            return $this->whereColumnIsAClosure($column, $joiner);
        }

        if ($value instanceof Closure) {
            return $this->whereValueIsAClosure($value, $column, $operator, $joiner);
        }
        
        // Operators that don't require a value
        $operators = ['IS NULL', 'IS NOT NULL', 'IS TRUE', 'IS NOT TRUE', 'IS FALSE', 'IS NOT FALSE'];
        
        if (!in_array($operator, $operators)) {
            if($value === null) {
                $value = $operator;
                $operator = '=';
            }
            
            $this->bindings[] = $value;
        }
        
        $this->components['where'][] = compact('column', 'operator', 'value', 'joiner');

        return $this;
    }

    public function whereRaw(string $where, array $values = [], string $joiner = 'AND'): static
    {
        $type = 'where_raw';

        $this->components['where'][] = compact('type', 'where', 'values', 'joiner');

        if ($values) {
            $this->bindings = array_merge($this->bindings, $values);
        }
        return $this;
    }

    public function orWhereRaw(string $where, array $values = []): static
    {
        $this->whereRaw($where, $values, 'OR');
        return $this;
    }

    public function orWhere($column, string $operator = null, $value = null): static
    {
        $this->where($column, $operator, $value, 'OR');
        return $this;
    }

    public function whereIn($column, $values = null, string $joiner = 'AND'): static
    {
        if ($values instanceof Closure) {
            $this->where($column, 'IN', $values, $joiner);
            return $this;
        }

        $operator = 'IN';
        $this->components['where'][] = compact('column', 'operator', 'values', 'joiner');
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function orWhereIn($column, $values): static
    {
        $this->whereIn($column, $values, 'OR');
        return $this;
    }

    public function whereNotIn($column, $values, string $joiner = 'AND'): static
    {
        if ($values instanceof Closure) {
            $this->where($column, 'NOT IN', $values, $joiner);
            return $this;
        }

        $operator = 'NOT IN';
        $this->components['where'][] = compact('column', 'operator', 'values', 'joiner');
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function orWhereNotIn($column, $values): static
    {
        $this->whereNotIn($column, $values, 'OR');
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->where($column, 'IS NULL');
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->where($column, 'IS NOT NULL');
        return $this;
    }

    public function orWhereNull(string $column): static
    {
        $this->orWhere($column, 'IS NULL');
        return $this;
    }

    public function orWhereNotNull(string $column): static
    {
        $this->orWhere($column, 'IS NOT NULL');
        return $this;
    }

    public function whereBetween(string $column, array $values, string $joiner = 'AND'): static
    {
        if(count($values) !== 2) {
            throw new \Exception('You must provide two values for the between clause');
        }

        $operator = 'BETWEEN';
        $type = 'where_between';
        $this->components['where'][] = compact('column', 'operator', 'values', 'joiner', 'type');
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function orWhereBetween($column, $values): static
    {
        $this->whereBetween($column, $values, 'OR');
        return $this;
    }

    public function whereNotBetween($column, $values, string $joiner = 'AND'): static
    {
        $operator = 'NOT BETWEEN';
        $type = 'where_not_between';
        $this->components['where'][] = compact('column', 'operator', 'values', 'joiner', 'type');
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function orWhereNotBetween($column, $values): static
    {
        $this->whereNotBetween($column, $values, 'OR');
        return $this;
    }

    public function whereTrue(string $column): static
    {
        $this->where($column, 'IS TRUE');
        return $this;
    }

    public function orWhereTrue(string $column): static
    {
        $this->orWhere($column, 'IS TRUE');
        return $this;
    }

    public function whereFalse(string $column): static
    {
        $this->where($column, 'IS FALSE');
        return $this;
    }

    public function orWhereFalse(string $column): static
    {
        $this->orWhere($column, 'IS FALSE');
        return $this;
    }

    public function whereExists(Closure $callback): static
    {
        $query = new Query();
        $callback($query);
        $this->components['where'][] = ['type' => 'where_exists', 'sub_query' => $query->toSql()];
        $this->bindings = array_merge($this->bindings, $query->bindings);
        return $this;
    }

    public function whereNotExists(Closure $callback): static
    {
        $query = new Query();
        $callback($query);
        $this->components['where'][] = ['type' => 'where_not_exists', 'sub_query' => $query->toSql()];
        return $this;
    }

    public function join(string $table, string $column1, string $column2, $type = 'INNER')
    {
        $this->components['join'][] = compact('table', 'column1', 'column2', 'type');
        return $this;
    }

    public function leftJoin(string $table, string $column1, string $column2)
    {
        $type = 'LEFT';
        $this->components['join'][] = compact('table', 'column1', 'column2', 'type');
        return $this;
    }

    public function rightJoin(string $table, string $column1, string $column2)
    {
        $type = 'RIGHT';
        $this->components['join'][] = compact('table', 'column1', 'column2', 'type');
        return $this;
    }

    public function groupBy(string ...$columns)
    {
        $this->components['group'] = $columns;
        return $this;
    }

    public function orderBy(string $column, $sort = 'ASC')
    {
        $this->components['order'][] = compact('column', 'sort');
        return $this;
    }

    public function limit(int $limit)
    {
        $this->components['limit'] = $limit;
        return $this;
    }

    public function offset(int $offset)
    {
        $this->components['offset'] = $offset;
        return $this;
    }

    /**
     * This method paginates the results of the query.
     *
     * @param integer|null $limit
     * @param integer|null $page
     * @return \Lightpack\Pagination\Pagination
     */
    public function paginate(?int $limit = null, ?int $page = null)
    {
        // Preserve the columns because calling count() will reset the columns.
        $columns = $this->columns;
        $total = $this->count();
        $this->columns = $columns;
        $page = $page ?? request()->input('page');
        $page = (int) $page;
        $page = $page > 0 ? $page : 1;

        $limit = $limit ?: request()->input('limit', 10);

        $this->components['limit'] = $limit > 0 ? $limit : 10;
        $this->components['offset'] = $limit * ($page - 1);

        if($total == 0) { // no need to query further
            return new Pagination([], $total);
        }

        $items = $this->fetchAll();

        return new Pagination($items, $total, $limit, $page);
    }

    public function count()
    {
        $this->columns = ['COUNT(*) AS total'];

        $query = $this->getCompiledCount();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        $this->columns = []; // so that pagination query can be reused

        return $result->total;
    }

    public function countBy(string $column)
    {
        $this->columns = [$column, 'COUNT(*) AS num'];
        $this->groupBy($column);

        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetchAll(\PDO::FETCH_OBJ);

        return $result;
    }

    public function sum(string $column)
    {
        $this->columns = ["SUM(`$column`) AS sum"];
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        return $result->sum;
    }

    public function avg(string $column)
    {
        $this->columns = ["AVG(`$column`) AS avg"];
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        return $result->avg;
    }

    public function min(string $column)
    {
        $this->columns = ["MIN(`$column`) AS min"];
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        return $result->min;
    }

    public function max(string $column)
    {
        $this->columns = ["MAX(`$column`) AS max"];
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        return $result->max;
    }

    public function __get(string $key)
    {
        if ($key === 'bindings') {
            return $this->bindings;
        }

        if ($key === 'table') {
            return $this->table;
        }

        return $this->components[$key] ?? null;
    }

    public function __set(string $key, $value)
    {
        if ($key === 'bindings') {
            $this->bindings = $value;
        }

        if ($key === 'table') {
            $this->table = $value;
        }

        if (isset($this->components[$key])) {
            $this->components[$key] = $value;
        }
    }

    protected function fetchAll()
    {
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetchAll(\PDO::FETCH_OBJ);
        $this->resetQuery();
        $this->resetBindings();
        $this->resetWhere();

        return $result;
    }

    public function all()
    {
        return $this->fetchAll();
    }

    protected function fetchOne()
    {
        $this->limit(1);
        $compiler = new Compiler($this);
        $query = $compiler->compileSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);
        $this->resetQuery();

        if($result == false) {
            return null;
        }

        return $result;
    }

    public function one()
    {
        return $this->fetchOne();
    }

    public function column(string $column)
    {
        $this->columns = [$column];
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetchColumn();
        $this->resetQuery();
        $this->resetBindings();
        $this->resetWhere();

        return $result;
    }

    public function chunk(int $chunkSize, callable $callback)
    {
        $records = $this->limit($chunkSize)->offset($page = 1)->all();

        while(count($records) > 0) {
            if(false === call_user_func($callback, $records)) {
                return;
            }

            $records = $this->limit($chunkSize)->offset(++$page)->all();
        }
    }

    public function getCompiledSelect()
    {
        $compiler = new Compiler($this);
        return $compiler->compileSelect();
    }

    public function getCompiledCount()
    {
        $compiler = new Compiler($this);
        return $compiler->compileCountQuery();
    }

    public function toSql()
    {
        return $this->getCompiledSelect();
    }

    public function resetQuery()
    {
        $this->components['alias'] = null;
        $this->components['columns'] = [];
        $this->components['distinct'] = false;
        $this->components['where'] = [];
        $this->components['join'] = [];
        $this->components['group'] = [];
        $this->components['order'] = [];
        $this->components['lock'] = [];
        $this->components['limit'] = null;
        $this->components['offset'] = null;
        $this->bindings = [];
    }

    public function resetWhere()
    {
        $this->components['where'] = [];
    }

    public function resetBindings()
    {
        $this->bindings = [];
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    protected function whereColumnIsAClosure(Closure $callback, string $joiner)
    {
        $query = new Query();
        call_user_func($callback, $query);
        $compiler = new Compiler($query);
        $subQuery = substr($compiler->compileWhere(), 6); // strip WHERE prefix
        $this->components['where'][] = ['type' => 'where_logical_group', 'sub_query' => $subQuery, 'joiner' => $joiner];
        $this->bindings = array_merge($this->bindings, $query->bindings);
        return $this;
    }

    protected function whereValueIsAClosure(Closure $callback, string $column, string $operator, string $joiner)
    {
        $query = new Query();
        call_user_func($callback, $query);
        $subQuery = $query->toSql();
        $this->components['where'][] = ['type' => 'where_sub_query', 'sub_query' => $subQuery, 'joiner' => $joiner, 'column' => $column, 'operator' => $operator];
        $this->bindings = array_merge($this->bindings, $query->bindings);
        return $this;
    }
}
