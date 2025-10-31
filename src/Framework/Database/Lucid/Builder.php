<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\Query\Query;
use Lightpack\Database\Lucid\Pagination;

class Builder extends Query
{
    /**
     * @var \Lightpack\Database\Lucid\Model
     */
    protected $model;

    /**
     * @var RelationLoader
     */
    protected $relationLoader;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->relationLoader = new RelationLoader($model);
        parent::__construct($model->getTableName(), $model->getConnection());
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function all()
    {
        $results = parent::all();
        return $this->hydrate($results);
    }

    public function one()
    {
        $result = parent::one();
        
        if ($result) {
            $result = $this->hydrateItem((array) $result);
        }

        return $result;
    }

    public function column(string $column)
    {
        return parent::column($column);
    }

    /**
     * Find a model by its primary key within the current query scope.
     * 
     * This method adds a WHERE clause for the primary key and returns a single model.
     * It respects any existing WHERE clauses from relationships or other query constraints.
     * 
     * @param mixed $id The primary key value to search for
     * @param bool $fail Whether to throw an exception if the record is not found (default: true)
     * @return Model|null The found model instance or null if not found (when $fail is false)
     * @throws RecordNotFoundException If $fail is true and the record is not found
     */
    public function find($id, bool $fail = true): ?Model
    {
        $primaryKey = $this->model->getPrimaryKey();
        
        // Add WHERE clause for the primary key
        $this->where($primaryKey, '=', $id);
        
        // Execute the query
        $result = $this->one();
        
        // Handle not found case
        if (!$result && $fail) {
            throw new \Lightpack\Exceptions\RecordNotFoundException(
                sprintf('%s: No record found for ID = %s', get_class($this->model), $id)
            );
        }
        
        return $result;
    }

    /**
     * @param integer|null $limit
     * @param integer|null $page
     * @return \Lightpack\Database\Lucid\Pagination
     */
    public function paginate(?int $limit = null, ?int $page = null)
    {
        $pagination = parent::paginate($limit, $page);
        
        if ($pagination->items()) {
            $items = $this->hydrate($pagination->items());
            return new Pagination($items, $pagination->total(), $pagination->limit(), $pagination->currentPage());
        }
        
        return new Pagination(new Collection([]), $pagination->total(), $pagination->limit(), $pagination->currentPage());
    }

    /**
     * Hydrate a collection of models from raw database results.
     */
    protected function hydrate(array $results): Collection
    {
        $models = [];
        $modelClass = get_class($this->model);

        foreach ($results as $result) {
            $model = new $modelClass;
            $model->setAttributes((array) $result);
            $models[] = $model;
        }

        $models = new Collection($models);
        $this->relationLoader->loadRelations($models);
        $this->relationLoader->loadRelationCounts($models);

        return $models;
    }

    /**
     * Hydrate a single model from raw database result.
     */
    protected function hydrateItem(array $attributes): Model
    {
        $model = clone $this->model;
        $model->setAttributes($attributes);

        $collection = new Collection($model);
        $this->relationLoader->loadRelations($collection);
        $this->relationLoader->loadRelationCounts($collection);

        return $model;
    }

    public function with(): self
    {
        $relations = func_get_args();
        $includes = is_array($relations[0]) ? $relations[0] : $relations;
        $this->relationLoader->setIncludes($includes);
        return $this;
    }

    public function withCount(): self
    {
        $relations = func_get_args();
        $includes = is_array($relations[0]) ? $relations[0] : $relations;
        $this->relationLoader->setCountIncludes($includes);
        return $this;
    }

    public function has(string $relation, ?string $operator = null, ?string $count = null, ?callable $constraint = null): self
    {
        if (!method_exists($this->model, $relation)) {
            throw new \Exception("Relation {$relation} does not exist.");
        }

        $relatingTable = $this->model->{$relation}()->table;

        // Count query
        if (!is_null($operator) && !is_null($count)) {
            return $this->buildCountQuery($relatingTable, $operator, $count, $constraint);
        }

        // Exists query
        return $this->buildExistsQuery($relatingTable, $constraint);
    }

    public function whereHas(string $relation, callable $constraint, ?string $operator = null, ?string $count = null): self
    {
        return $this->has($relation, $operator, $count, $constraint);
    }

    protected function buildCountQuery(string $relatingTable, string $operator, string $count, ?callable $constraint): self
    {
        $query = $this->model->getConnection()->table($relatingTable);
        $bindings = [];

        $subQueryCallback = function ($q) use ($relatingTable) {
            $q->from($relatingTable)
                ->select('COUNT(*)')
                ->whereRaw($this->model->getTableName() . '.' . $this->model->getPrimaryKey() . ' = ' . $relatingTable . '.' . $this->model->getRelatingKey());
        };

        $subQueryCallback($query);

        if ($constraint) {
            $constraint($query);
            $bindings = $query->bindings;
        }

        $bindings[] = $count;
        return $this->whereRaw('(' . $query->toSql() . ')' . ' ' . $operator . ' ' . '?', $bindings);
    }

    protected function buildExistsQuery(string $relatingTable, ?callable $constraint): self
    {
        $subQuery = function ($q) use ($relatingTable, $constraint) {
            $q->from($this->model->{$relatingTable}()->table)
                ->whereRaw($this->model->getTableName() . '.' . $this->model->getPrimaryKey() . ' = ' . $relatingTable . '.' . $this->model->getRelatingKey());

            if ($constraint) {
                $constraint($q);
            }
        };

        return $this->whereExists($subQuery);
    }

    /**
     * Eager load relations for a collection.
     */
    public function eagerLoadRelations(Collection $models): void
    {
        $this->relationLoader->loadRelations($models);
    }

    /**
     * Eager load relation counts for a collection.
     */
    public function eagerLoadRelationsCount(Collection $models): void
    {
        $this->relationLoader->loadRelationCounts($models);
    }
}
