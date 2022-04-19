<?php

namespace Lightpack\Database\Query;

use Closure;
use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Lucid\Pagination;
use Lightpack\Database\Pdo;

class Query
{
    private $connection;
    private $table;
    private $model;
    private $bindings = [];
    private $components = [
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

    public function setConnection(Pdo $connection)
    {
        $this->connection = $connection;
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

    public function where($column, string $operator = null, $value = null, string $joiner = 'AND'): self
    {
        if ($column instanceof Closure) {
            $query = new Query();
            call_user_func($column, $query);
            $compiler = new Compiler($query);
            $subQuery = substr($compiler->compileWhere(), 6); // strip WHERE prefix
            $this->components['where'][] = ['type' => 'where_logical_group', 'sub_query' => $subQuery, 'joiner' => $joiner];
            $this->bindings = array_merge($this->bindings, $query->bindings);
            return $this;
        }

        if ($value instanceof Closure) {
            $query = new Query();
            call_user_func($value, $query);
            $compiler = new Compiler($query);
            $subQuery = $query->toSql();
            $this->components['where'][] = ['type' => 'where_sub_query', 'sub_query' => $subQuery, 'joiner' => $joiner, 'column' => $column, 'operator' => $operator];  
            $this->bindings = array_merge($this->bindings, $query->bindings);
            return $this;
        }

        $this->components['where'][] = compact('column', 'operator', 'value', 'joiner');

        if ($operator) {
            $this->bindings[] = $value;
        }

        return $this;
    }

    public function whereRaw(string $where, array $values = [], string $joiner = null): self
    {
        $type = 'where_raw';

        $this->components['where'][] = compact('type', 'where', 'values', 'joiner');

        if ($values) {
            $this->bindings = array_merge($this->bindings, $values);
        }
        return $this;
    }

    public function andWhereRaw(string $where, array $values = []): self
    {
        $this->whereRaw($where, $values, 'AND');
        return $this;
    }

    public function orWhereRaw(string $where, array $values = []): self
    {
        $this->whereRaw($where, $values, 'OR');
        return $this;
    }

    public function andWhere(string $column, string $operator, string $value): self
    {
        $this->where($column, $operator, $value, 'AND');
        return $this;
    }

    public function orWhere(string $column, string $operator, string $value): self
    {
        $this->where($column, $operator, $value, 'OR');
        return $this;
    }

    public function whereIn(string $column, array $values, string $joiner = 'AND'): self
    {
        $operator = 'IN';
        $this->components['where'][] = compact('column', 'operator', 'values', 'joiner');
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function andWhereIn(string $column, array $values): self
    {
        $this->whereIn($column, $values, 'AND');
        return $this;
    }

    public function orWhereIn(string $column, array $values): self
    {
        $this->whereIn($column, $values, 'OR');
        return $this;
    }

    public function whereNotIn(string $column, array $values, string $joiner = 'AND'): self
    {
        $operator = 'NOT IN';
        $this->components['where'][] = compact('column', 'operator', 'values', 'joiner');
        $this->bindings = array_merge($this->bindings, $values);
        return $this;

        $this->whereIn($column, $values, 'AND', true);
        return $this;
    }

    public function andWhereNotIn(string $column, array $values): self
    {
        $this->whereNotIn($column, $values, 'AND');
        return $this;
    }

    public function orWhereNotIn(string $column, array $values): self
    {
        $this->whereNotIn($column, $values, 'OR');
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->where($column, '', 'IS NULL');
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->where($column, '', 'IS NOT NULL');
        return $this;
    }

    public function andWhereNull(string $column): self
    {
        $this->andWhere($column, '', 'IS NULL');
        return $this;
    }

    public function andWhereNotNull(string $column): self
    {
        $this->andWhere($column, '', 'IS NOT NULL');
        return $this;
    }

    public function orWhereNull(string $column): self
    {
        $this->orWhere($column, '', 'IS NULL');
        return $this;
    }

    public function orWhereNotNull(string $column): self
    {
        $this->orWhere($column, '', 'IS NOT NULL');
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

    public function paginate(int $limit = null, int $page = null)
    {
        $columns = $this->columns;
        $total = $this->count();
        $this->columns = $columns;
        $page = $page ?? app('request')->get('page');
        $page = (int) $page;
        $page = $page > 0 ? $page : 1;

        $limit = $limit ?: app('request')->get('limit', 10);

        $this->components['limit'] = $limit > 0 ? $limit : 10;
        $this->components['offset'] = $limit * ($page - 1);

        $items = $this->fetchAll();

        return new Pagination($total, $limit, $page, $items);
        // return $items;
    }

    public function count()
    {
        $this->columns = ['count(*) AS num'];

        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        $this->columns = []; // so that pagination query can be reused

        return $result->num;
    }

    public function groupCount(string $column, ?Closure $callback = null)
    {
        if ($callback) {
            $callback($this);
        }

        $this->columns = [$column, 'count(*) AS num'];
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

    public function fetchAll(bool $assoc = false)
    {
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetchAll($assoc ? \PDO::FETCH_ASSOC : \PDO::FETCH_OBJ);
        $this->resetQuery();
        $this->resetBindings();
        $this->resetWhere();

        if ($this->model) {
            // $result = $this->model->hydrate($result ?? []);
            return static::hydrate($result);
        }

        return $result;
    }

    public function all(bool $assoc = false)
    {
        return $this->fetchAll($assoc);
    }

    public function fetchOne(bool $assoc = false)
    {
        $compiler = new Compiler($this);
        $query = $compiler->compileSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch($assoc ? \PDO::FETCH_ASSOC : \PDO::FETCH_OBJ);
        $this->resetQuery();

        if ($result && $this->model) {
            $result = (array) $result;
            $result = static::hydrateItem($result);
        }

        return $result;
    }

    public function one(bool $assoc = false)
    {
        return $this->fetchOne($assoc);
    }

    public function getCompiledSelect()
    {
        $compiler = new Compiler($this);
        return $compiler->compileSelect();
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
}
