<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\Query\Query;

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
        $this->injectSubqueriesForOrdering();
        $results = parent::all();

        return $this->hydrate($results);
    }

    public function one()
    {
        $this->injectSubqueriesForOrdering();
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
        if (! $result && $fail) {
            throw new \Lightpack\Exceptions\RecordNotFoundException(
                sprintf('%s: No record found for ID = %s', get_class($this->model), $id)
            );
        }

        return $result;
    }

    /**
     * @param int|null $limit
     * @param int|null $page
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
        $this->relationLoader->loadRelationAggregates($models);

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
        $this->relationLoader->loadRelationAggregates($collection);

        return $model;
    }

    public function with(): self
    {
        $relations = func_get_args();
        $includes = is_array($relations[0]) ? $relations[0] : $relations;
        $this->relationLoader->setIncludes($includes);

        return $this;
    }

    /**
     * Eager load relation counts. Without orderBy the efficient GROUP BY path runs
     * after hydration. With orderBy('relation_count') a correlated COUNT(*) subquery
     * is injected into SELECT instead, and the GROUP BY path is skipped for that relation.
     */
    public function withCount(): self
    {
        $relations = func_get_args();
        $includes = is_array($relations[0]) ? $relations[0] : $relations;
        $this->relationLoader->setCountIncludes($includes);

        return $this;
    }

    public function withSum(): self
    {
        $args = func_get_args();
        $relation = $args[0];
        $column = $args[1] ?? null;
        $includes = is_array($relation) ? $relation : [$relation];
        $this->relationLoader->setSumIncludes($includes, $column);

        return $this;
    }

    public function withAvg(): self
    {
        $args = func_get_args();
        $relation = $args[0];
        $column = $args[1] ?? null;
        $includes = is_array($relation) ? $relation : [$relation];
        $this->relationLoader->setAvgIncludes($includes, $column);

        return $this;
    }

    public function withMin(): self
    {
        $args = func_get_args();
        $relation = $args[0];
        $column = $args[1] ?? null;
        $includes = is_array($relation) ? $relation : [$relation];
        $this->relationLoader->setMinIncludes($includes, $column);

        return $this;
    }

    public function withMax(): self
    {
        $args = func_get_args();
        $relation = $args[0];
        $column = $args[1] ?? null;
        $includes = is_array($relation) ? $relation : [$relation];
        $this->relationLoader->setMaxIncludes($includes, $column);

        return $this;
    }

    public function has(string $relation, ?string $operator = null, ?string $count = null, ?callable $constraint = null): self
    {
        if (! method_exists($this->model, $relation)) {
            throw new \Exception("Relation {$relation} does not exist.");
        }

        $this->model->setEagerLoading(true);
        $relatingTable = $this->model->{$relation}()->table;
        $this->model->setEagerLoading(false);

        // Count query
        if (! is_null($operator) && ! is_null($count)) {
            return $this->buildCountQuery($relatingTable, $operator, $count, $constraint);
        }

        // Exists query
        return $this->buildExistsQuery($relatingTable, $constraint);
    }

    public function whereHas(string $relation, callable $constraint, ?string $operator = null, ?string $count = null): self
    {
        return $this->has($relation, $operator, $count, $constraint);
    }

    public function doesntHave(string $relation, ?callable $constraint = null): self
    {
        if (! method_exists($this->model, $relation)) {
            throw new \Exception("Relation {$relation} does not exist.");
        }

        $this->model->setEagerLoading(true);
        $relatingTable = $this->model->{$relation}()->table;
        $this->model->setEagerLoading(false);

        return $this->buildNotExistsQuery($relatingTable, $constraint);
    }

    public function whereDoesntHave(string $relation, callable $constraint): self
    {
        return $this->doesntHave($relation, $constraint);
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
        $relatingKey = $this->model->getRelatingKey();
        $parentTable = $this->model->getTableName();
        $primaryKey = $this->model->getPrimaryKey();

        $subQuery = function ($q) use ($relatingTable, $relatingKey, $parentTable, $primaryKey, $constraint) {
            $q->from($relatingTable)
                ->whereRaw("{$parentTable}.{$primaryKey} = {$relatingTable}.{$relatingKey}");

            if ($constraint) {
                $constraint($q);
            }
        };

        return $this->whereExists($subQuery);
    }

    protected function buildNotExistsQuery(string $relatingTable, ?callable $constraint): self
    {
        $relatingKey = $this->model->getRelatingKey();
        $parentTable = $this->model->getTableName();
        $primaryKey = $this->model->getPrimaryKey();

        $subQuery = function ($q) use ($relatingTable, $relatingKey, $parentTable, $primaryKey, $constraint) {
            $q->from($relatingTable)
                ->whereRaw("{$parentTable}.{$primaryKey} = {$relatingTable}.{$relatingKey}");

            if ($constraint) {
                $constraint($q);
            }
        };

        return $this->whereNotExists($subQuery);
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

    /**
     * Eager load relation aggregates for a collection.
     */
    public function eagerLoadRelationsAggregate(Collection $models): void
    {
        $this->relationLoader->loadRelationAggregates($models);
    }

    // Relation aggregates (withCount/withSum/etc.) normally fire separate GROUP BY queries
    // after the main query. But using them with orderBy() requires a correlated subquery in
    // SELECT so MySQL can sort by that column. Just before all()/one() we inject correlated
    // subqueries for any aggregate column that appears in ORDER BY. Relations NOT used with
    // orderBy() still use the cheaper GROUP BY path.
    private function injectSubqueriesForOrdering(): void
    {
        foreach ($this->components['order'] ?? [] as $order) {
            $this->tryInjectCountSubquery($order['column']);
            $this->tryInjectAggregateSubquery($order['column']);
        }
    }

    private function tryInjectCountSubquery(string $column): void
    {
        if (! str_ends_with($column, '_count')) {
            return;
        }

        $relation = substr($column, 0, -6);

        if (! $this->relationLoader->hasCountInclude($relation)) {
            return;
        }

        $constraint = $this->relationLoader->getCountConstraint($relation);
        $this->injectCountSubquery($relation, $constraint);
    }

    private function tryInjectAggregateSubquery(string $column): void
    {
        foreach (['sum', 'avg', 'min', 'max'] as $type) {
            $marker = '_' . $type . '_';

            if (! str_contains($column, $marker)) {
                continue;
            }

            [$relation, $aggColumn] = explode($marker, $column, 2);

            if (! $this->relationLoader->hasAggregateInclude($type, $relation, $aggColumn)) {
                return;
            }

            $config = $this->relationLoader->getAggregateConfig($type, $relation, $aggColumn);
            $this->injectAggregateSubquery($relation, $type, $aggColumn, $config['constraint'] ?? null);
            $this->relationLoader->removeAggregateInclude($type, $relation, $aggColumn);

            return;
        }
    }

    private function injectCountSubquery(string $relation, ?callable $constraint = null): void
    {
        $this->model->setEagerLoading(true);
        $query = $this->model->{$relation}();
        $relationType = $this->model->getRelationType();

        if ($relationType === 'morphedByMany' && $query instanceof PolymorphicPivot) {
            $this->injectPolymorphicPivotCountSubquery($query, $relation, $query->getAssociateKey());
            $this->model->setEagerLoading(false);

            return;
        }

        if ($relationType === 'morphToMany' && $query instanceof PolymorphicPivot) {
            $this->injectPolymorphicPivotCountSubquery($query, $relation, 'morph_id');
            $this->model->setEagerLoading(false);

            return;
        }

        if ($relationType === 'pivot' && $query instanceof Pivot) {
            $this->injectPivotCountSubquery($query, $relation);
            $this->model->setEagerLoading(false);

            return;
        }

        $sql = $this->buildHasManySubquery($query, $relationType, 'COUNT(*)', $constraint);
        $this->model->setEagerLoading(false);

        if ($sql === null) {
            return;
        }

        $this->applySubqueryAlias($sql, "{$relation}_count");
        $this->relationLoader->removeCountInclude($relation);
    }

    private function injectAggregateSubquery(string $relation, string $type, string $column, ?callable $constraint = null): void
    {
        $this->model->setEagerLoading(true);
        $query = $this->model->{$relation}();
        $relationType = $this->model->getRelationType();

        $sql = $this->buildHasManySubquery($query, $relationType, strtoupper($type) . "(`{$column}`)", $constraint);
        $this->model->setEagerLoading(false);

        if ($sql === null) {
            return;
        }

        $this->applySubqueryAlias($sql, "{$relation}_{$type}_{$column}");
    }

    private function buildHasManySubquery(object $query, string $relationType, string $expression, ?callable $constraint): ?string
    {
        if (! in_array($relationType, ['hasMany', 'hasManyThrough'])) {
            return null;
        }

        $parentTable = $this->model->getTableName();
        $primaryKey = $this->model->getPrimaryKey();
        $relatingKey = $this->model->getRelatingKey();

        $subQuery = clone $query;
        $subQuery->columns = [$expression];
        $subQuery->select_raw = [];

        if ($constraint) {
            $constraint($subQuery);
        }

        if ($relationType === 'hasManyThrough') {
            $joins = $subQuery->join ?? [];
            $throughTable = $joins[0]['table'] ?? $subQuery->table;
            $subQuery->whereRaw("{$throughTable}.{$relatingKey} = {$parentTable}.{$primaryKey}");
        } else {
            $subQuery->whereRaw("{$subQuery->table}.{$relatingKey} = {$parentTable}.{$primaryKey}");
        }

        if ($subQuery->bindings) {
            // Prepend — SELECT is compiled before WHERE, so subquery bindings come first.
            $this->bindings = array_merge($subQuery->bindings, $this->bindings);
        }

        return $subQuery->toSql();
    }

    private function injectPolymorphicPivotCountSubquery(PolymorphicPivot $query, string $relation, string $parentKey): void
    {
        $pivotTable = $query->getPivotTableName();
        $morphType = $query->getMorphType();
        $parentTable = $this->model->getTableName();
        $primaryKey = $this->model->getPrimaryKey();

        $sql = "SELECT COUNT(*) FROM `{$pivotTable}` WHERE `morph_type` = '{$morphType}' AND `{$parentKey}` = `{$parentTable}`.`{$primaryKey}`";

        $this->applySubqueryAlias($sql, "{$relation}_count");
        $this->relationLoader->removeCountInclude($relation);
    }

    private function injectPivotCountSubquery(Pivot $query, string $relation): void
    {
        $pivotTable = $query->getPivotTableName();
        $foreignKey = $this->model->getRelatingKey();
        $parentTable = $this->model->getTableName();
        $primaryKey = $this->model->getPrimaryKey();

        $sql = "SELECT COUNT(*) FROM `{$pivotTable}` WHERE `{$foreignKey}` = `{$parentTable}`.`{$primaryKey}`";

        $this->applySubqueryAlias($sql, "{$relation}_count");
        $this->relationLoader->removeCountInclude($relation);
    }

    private function applySubqueryAlias(string $sql, string $alias): void
    {
        $this->components['select_raw'][] = "({$sql}) AS {$alias}";

        if (empty($this->components['columns'])) {
            // MySQL rejects a bare subquery in SELECT without at least one named column.
            $this->select('*');
        }
    }
}
