<?php

namespace Lightpack\Database\Query;

use Closure;
use Lightpack\Database\Lucid\Collection;
use Lightpack\Database\Lucid\Model;
use Lightpack\Pagination\Pagination as BasePagination;
use Lightpack\Database\Lucid\Pagination as LucidPagination;
use Lightpack\Database\Pdo;

class Query
{
    protected $connection;
    protected $table;
    protected $model;
    protected $bindings = [];
    protected $components = [
        'alias' => null,
        'columns' => [],
        'distinct' => false,
        'join' => [],
        'where' => [],
        'group' => [],
        'order' => [],
        'limit' => null,
        'offset' => null,
    ];

    public function __construct($subject = null, Pdo $connection = null)
    {
        if ($subject instanceof Model) {
            $this->model = $subject;
            $this->table = $subject->getTableName();
        } else {
            $this->table = $subject;
        }

        $this->connection = $connection ?? app('db');
    }

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setConnection(Pdo $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function insert(array $data)
    {
        $compiler = new Compiler($this);
        $this->bindings = array_values($data);
        $query = $compiler->compileInsert(array_keys($data));
        $result = $this->connection->query($query, $this->bindings);
        $this->resetQuery();
        return $result;
    }

    public function bulkInsert(array $data)
    {
        // verify that data is an array of arrays
        if (empty($data) || array_values($data) !== $data) {
            throw new \Exception('bulkInsert() expects an array of arrays');
        }

        // Loop data to prepare for parameter binding
        foreach ($data as $row) {
            $this->bindings = array_merge($this->bindings, array_values($row));
        }

        $columns = array_keys($data[0]);
        $compiler = new Compiler($this);
        $query = $compiler->compileBulkInsert($columns, $data);
        $result = $this->connection->query($query, $this->bindings);
        $this->resetQuery();
        return $result;
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

    public function alias(string $alias): self
    {
        $this->components['alias'] = $alias;
        return $this;
    }

    public function select(string ...$columns): self
    {
        $this->components['columns'] = $columns;
        return $this;
    }

    public function from(string $table, string $alias = null): self
    {
        $this->table = $table;
        $this->components['alias'] = $alias;
        return $this;
    }

    public function distinct(): self
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
    public function where($column, string $operator = null, $value = null, string $joiner = 'AND'): self
    {
        if ($column instanceof Closure) {
            return $this->whereColumnIsAClosure($column, $joiner);
        }

        if ($value instanceof Closure) {
            return $this->whereValueIsAClosure($value, $column, $operator, $joiner);
        }

        $this->components['where'][] = compact('column', 'operator', 'value', 'joiner');

        // Operators that don't require a value
        $operators = ['IS NULL', 'IS NOT NULL', 'IS TRUE', 'IS NOT TRUE', 'IS FALSE', 'IS NOT FALSE'];

        if (!in_array($operator, $operators)) {
            $this->bindings[] = $value;
        }

        return $this;
    }

    public function whereRaw(string $where, array $values = [], string $joiner = 'AND'): self
    {
        $type = 'where_raw';

        $this->components['where'][] = compact('type', 'where', 'values', 'joiner');

        if ($values) {
            $this->bindings = array_merge($this->bindings, $values);
        }
        return $this;
    }

    public function orWhereRaw(string $where, array $values = []): self
    {
        $this->whereRaw($where, $values, 'OR');
        return $this;
    }

    public function orWhere($column, string $operator = null, $value = null): self
    {
        $this->where($column, $operator, $value, 'OR');
        return $this;
    }

    public function whereIn($column, $values = null, string $joiner = 'AND'): self
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

    public function orWhereIn($column, $values): self
    {
        $this->whereIn($column, $values, 'OR');
        return $this;
    }

    public function whereNotIn($column, $values, string $joiner = 'AND'): self
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

    public function orWhereNotIn($column, $values): self
    {
        $this->whereNotIn($column, $values, 'OR');
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->where($column, 'IS NULL');
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->where($column, 'IS NOT NULL');
        return $this;
    }

    public function orWhereNull(string $column): self
    {
        $this->orWhere($column, 'IS NULL');
        return $this;
    }

    public function orWhereNotNull(string $column): self
    {
        $this->orWhere($column, 'IS NOT NULL');
        return $this;
    }

    public function whereBetween(string $column, array $values, string $joiner = 'AND'): self
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

    public function orWhereBetween($column, $values): self
    {
        $this->whereBetween($column, $values, 'OR');
        return $this;
    }

    public function whereNotBetween($column, $values, string $joiner = 'AND'): self
    {
        $operator = 'NOT BETWEEN';
        $type = 'where_not_between';
        $this->components['where'][] = compact('column', 'operator', 'values', 'joiner', 'type');
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function orWhereNotBetween($column, $values): self
    {
        $this->whereNotBetween($column, $values, 'OR');
        return $this;
    }

    public function whereTrue(string $column): self
    {
        $this->where($column, 'IS TRUE');
        return $this;
    }

    public function orWhereTrue(string $column): self
    {
        $this->orWhere($column, 'IS TRUE');
        return $this;
    }

    public function whereFalse(string $column): self
    {
        $this->where($column, 'IS FALSE');
        return $this;
    }

    public function orWhereFalse(string $column): self
    {
        $this->orWhere($column, 'IS FALSE');
        return $this;
    }

    public function whereExists(Closure $callback): self
    {
        $query = new Query();
        $callback($query);
        $this->components['where'][] = ['type' => 'where_exists', 'sub_query' => $query->toSql()];
        $this->bindings = array_merge($this->bindings, $query->bindings);
        return $this;
    }

    public function whereNotExists(Closure $callback): self
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
     * @return \Lightpack\Pagination\Pagination|\Lightpack\Database\Lucid\Pagination
     */
    public function paginate(int $limit = null, int $page = null)
    {
        // Preserve the columns because calling count() will reset the columns.
        $columns = $this->columns;
        $total = $this->count();
        $this->columns = $columns;
        $page = $page ?? request()->get('page');
        $page = (int) $page;
        $page = $page > 0 ? $page : 1;

        $limit = $limit ?: request()->get('limit', 10);

        $this->components['limit'] = $limit > 0 ? $limit : 10;
        $this->components['offset'] = $limit * ($page - 1);

        if($total == 0) { // no need to query further
            if($this->model) {
               return new LucidPagination($total, $limit, $page, new Collection([]));
            } else {
                return new BasePagination($total);
            }
        }

        $items = $this->fetchAll();

        if($items instanceof Collection) {
            return new LucidPagination($total, $limit, $page, $items);
        }

        return new BasePagination($total, $limit, $page, $items);
    }

    public function count()
    {
        $this->columns = ['COUNT(*) AS num'];

        $query = $this->getCompiledCount();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        $this->columns = []; // so that pagination query can be reused

        return $result->num;
    }

    public function countBy(string $column)
    {
        $this->columns = [$column, 'COUNT(*) AS num'];
        $this->groupBy($column);

        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetchAll(\PDO::FETCH_OBJ);

        return $result;
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

        if ($this->model) {
            return static::hydrate($result);
        }

        return $result;
    }

    public function all()
    {
        return $this->fetchAll();
    }

    protected function fetchOne()
    {
        $compiler = new Compiler($this);
        $query = $compiler->compileSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);
        $this->resetQuery();

        if ($result && $this->model) {
            $result = (array) $result;
            $result = static::hydrateItem($result);
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
