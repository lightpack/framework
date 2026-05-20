<?php

namespace Lightpack\Database\Lucid;

class RelationLoader
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var array Relations to eager load
     */
    protected $includes = [];

    /**
     * @var array Relations to count
     */
    protected $countIncludes = [];

    /**
     * @var array Relations to sum
     */
    protected $sumIncludes = [];

    /**
     * @var array Relations to avg
     */
    protected $avgIncludes = [];

    /**
     * @var array Relations to min
     */
    protected $minIncludes = [];

    /**
     * @var array Relations to max
     */
    protected $maxIncludes = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Set relations to eager load
     */
    public function setIncludes(array $includes): void
    {
        $this->includes = $includes;
    }

    /**
     * Set relations to count
     */
    public function setCountIncludes(array $includes): void
    {
        $this->countIncludes = array_merge($this->countIncludes, $includes);
    }

    public function hasCountInclude(string $relation): bool
    {
        return in_array($relation, $this->countIncludes) || isset($this->countIncludes[$relation]);
    }

    public function removeCountInclude(string $relation): void
    {
        $key = array_search($relation, $this->countIncludes);
        if ($key !== false) {
            unset($this->countIncludes[$key]);
        }
        unset($this->countIncludes[$relation]);
    }

    public function getCountConstraint(string $relation): ?callable
    {
        if (isset($this->countIncludes[$relation]) && is_callable($this->countIncludes[$relation])) {
            return $this->countIncludes[$relation];
        }

        return null;
    }

    /**
     * Set relations to sum
     */
    public function setSumIncludes(array $includes, ?string $column = null): void
    {
        $this->setAggregateIncludes('sum', $includes, $column);
    }

    /**
     * Set relations to avg
     */
    public function setAvgIncludes(array $includes, ?string $column = null): void
    {
        $this->setAggregateIncludes('avg', $includes, $column);
    }

    /**
     * Set relations to min
     */
    public function setMinIncludes(array $includes, ?string $column = null): void
    {
        $this->setAggregateIncludes('min', $includes, $column);
    }

    /**
     * Set relations to max
     */
    public function setMaxIncludes(array $includes, ?string $column = null): void
    {
        $this->setAggregateIncludes('max', $includes, $column);
    }

    public function hasAggregateInclude(string $type, string $relation, string $column): bool
    {
        $property = $type . 'Includes';

        return isset($this->{$property}[$relation . ':' . $column]);
    }

    public function getAggregateConfig(string $type, string $relation, string $column): ?array
    {
        $property = $type . 'Includes';

        return $this->{$property}[$relation . ':' . $column] ?? null;
    }

    public function removeAggregateInclude(string $type, string $relation, string $column): void
    {
        $property = $type . 'Includes';
        unset($this->{$property}[$relation . ':' . $column]);
    }

    /**
     * Store aggregate includes for a given type
     */
    protected function setAggregateIncludes(string $type, array $includes, ?string $column): void
    {
        $property = $type . 'Includes';

        foreach ($includes as $key => $value) {
            $relation = is_callable($value) ? $key : $value;
            $constraint = is_callable($value) ? $value : null;
            $this->{$property}[$relation . ':' . $column] = [
                'relation' => $relation,
                'column' => $column,
                'constraint' => $constraint,
            ];
        }
    }

    /**
     * Load relations for a collection of models
     */
    public function loadRelations(Collection $models): void
    {
        foreach ($this->includes as $key => $value) {
            $this->loadRelation($models, $key, $value);
        }
    }

    /**
     * Load relation counts for a collection of models
     */
    public function loadRelationCounts(Collection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        foreach ($this->countIncludes as $key => $value) {
            $this->loadRelationCount($models, $key, $value);
        }
    }

    /**
     * Load a single relation
     */
    protected function loadRelation(Collection $models, $key, $value): void
    {
        $constraint = null;
        $include = $value;

        if (is_callable($value)) {
            if (! is_string($key)) {
                throw new \Exception("Relation key must be a string.");
            }
            $constraint = $value;
            $include = $key;
        }

        $relation = explode('.', $include)[0];

        // Skip if already loaded
        if ($models->any($relation)) {
            return;
        }

        if (! method_exists($this->model, $relation)) {
            throw new \Exception("Trying to eager load `{$relation}` but no relationship has been defined.");
        }

        $this->model->setEagerLoading(true);
        $query = $this->model->{$relation}();
        $ids = $this->getRelationIds($models, $this->model->getRelationType());
        $pivotKeyName = $this->getPivotKeyName($this->model->getRelationType());

        if (! $ids) {
            $this->setRelationResults($models, new Collection([]), $relation);
            $this->model->setEagerLoading(false);

            return;
        }

        if ($constraint) {
            $constraint($query);
        }

        $relatingKey = $pivotKeyName ?? $this->model->getRelatingKey();

        // If a limit is set on the relation query, use a window function to enforce
        // per-parent limiting. Otherwise the WHERE IN approach is more efficient.
        if ($query->limit && in_array($this->model->getRelationType(), ['hasMany', 'hasManyThrough'])) {
            $children = $this->loadLimitedRelation($query, $ids, $relatingKey);
            $this->model->setEagerLoading(false);
            $this->setRelationResults($models, $children, $relation);
            $this->loadNestedRelations($models, $include, $relation);

            return;
        }

        $children = $query->whereIn($relatingKey, $ids)->all();
        $this->model->setEagerLoading(false);

        $this->setRelationResults($models, $children, $relation);
        $this->loadNestedRelations($models, $include, $relation);
    }

    /**
     * Load a relation with per-parent limiting using ROW_NUMBER() window function.
     *
     * This is used when a constraint sets ->limit(N) on a hasMany/hasManyThrough relation.
     * A single query with a window function enforces N records per parent.
     */
    protected function loadLimitedRelation($query, array $ids, string $relatingKey): Collection
    {
        $limit = $query->limit;

        // Build ORDER BY clause for the window function
        $orderSql = '';
        if ($query->order) {
            $parts = [];
            foreach ($query->order as $order) {
                $parts[] = '`' . $order['column'] . '` ' . $order['sort'];
            }
            $orderSql = 'ORDER BY ' . implode(', ', $parts);
        }

        // Clone the query, strip limit/offset, add the WHERE IN for parent IDs
        $innerQuery = clone $query;
        $innerQuery->limit = null;
        $innerQuery->offset = null;
        $innerQuery->whereIn($relatingKey, $ids);

        $innerSql = $innerQuery->toSql();
        $bindings = $innerQuery->bindings;

        // Wrap with ROW_NUMBER() partitioned by the relating key
        $sql = "SELECT * FROM (
            SELECT *, ROW_NUMBER() OVER (PARTITION BY `{$relatingKey}` {$orderSql}) AS __lp_rn
            FROM ({$innerSql}) AS __lp_inner
        ) AS __lp_ranked WHERE __lp_rn <= {$limit}";

        $results = $this->model->getConnection()->query($sql, $bindings)->fetchAll(\PDO::FETCH_OBJ);

        foreach ($results as $result) {
            unset($result->__lp_rn);
        }

        return new Collection($results);
    }

    /**
     * Load count for a single relation
     */
    protected function loadRelationCount(Collection $models, $key, $value): void
    {
        $constraint = null;
        $include = $value;

        if (is_callable($value)) {
            if (! is_string($key)) {
                throw new \Exception("Relation key must be a string.");
            }
            $constraint = $value;
            $include = $key;
        }

        $this->model->setEagerLoading(true);
        $query = $this->model->{$include}();
        $relationType = $this->model->getRelationType();

        if (in_array($relationType, ['hasMany', 'hasManyThrough'])) {
            if ($constraint) {
                $constraint($query);
            }

            $relatingKey = $this->model->getRelatingKey();
            $counts = $query->whereIn($relatingKey, $models->ids())->countBy($relatingKey)->all();
            $this->model->setEagerLoading(false);

            foreach ($models as $model) {
                $model->{$include . '_count'} = 0;
                foreach ($counts as $count) {
                    if ($count->{$relatingKey} === $model->{$model->getPrimaryKey()}) {
                        $model->{$include . '_count'} = (int) $count->count;

                        break;
                    }
                }
            }
        } elseif ($relationType === 'morphedByMany' && $query instanceof PolymorphicPivot) {
            $this->loadPolymorphicPivotCount($models, $query, $include, $query->getAssociateKey());
        } elseif ($relationType === 'morphToMany' && $query instanceof PolymorphicPivot) {
            $this->loadPolymorphicPivotCount($models, $query, $include, 'morph_id');
        } elseif ($relationType === 'pivot' && $query instanceof Pivot) {
            $this->loadPivotCount($models, $query, $include);
        }

        $this->model->setEagerLoading(false);
    }

    private function loadPolymorphicPivotCount(Collection $models, PolymorphicPivot $query, string $include, string $groupKey): void
    {
        $morphType = $query->getMorphType();
        $pivotTable = $query->getPivotTableName();

        $pivotQuery = new \Lightpack\Database\Query\Query($pivotTable, $query->getConnection());
        $counts = $pivotQuery
            ->where('morph_type', '=', $morphType)
            ->whereIn($groupKey, $models->ids())
            ->countBy($groupKey)
            ->all();

        foreach ($models as $model) {
            $model->{$include . '_count'} = 0;
            foreach ($counts as $count) {
                if ($count->{$groupKey} == $model->{$model->getPrimaryKey()}) {
                    $model->{$include . '_count'} = (int) $count->count;

                    break;
                }
            }
        }
    }

    private function loadPivotCount(Collection $models, Pivot $query, string $include): void
    {
        $foreignKey = $this->model->getRelatingKey();
        $pivotTable = $query->getPivotTableName();

        $pivotQuery = new \Lightpack\Database\Query\Query($pivotTable, $query->getConnection());
        $counts = $pivotQuery
            ->whereIn($foreignKey, $models->ids())
            ->countBy($foreignKey)
            ->all();

        foreach ($models as $model) {
            $model->{$include . '_count'} = 0;
            foreach ($counts as $count) {
                if ($count->{$foreignKey} == $model->{$model->getPrimaryKey()}) {
                    $model->{$include . '_count'} = (int) $count->count;

                    break;
                }
            }
        }
    }

    /**
     * Load relation aggregates for a collection of models
     */
    public function loadRelationAggregates(Collection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        foreach (['sum', 'avg', 'min', 'max'] as $type) {
            $property = $type . 'Includes';
            foreach ($this->{$property} as $config) {
                $this->loadRelationAggregate($models, $type, $config['relation'], $config['column'], $config['constraint']);
            }
        }
    }

    /**
     * Load a single aggregate for a relation
     */
    protected function loadRelationAggregate(Collection $models, string $type, string $include, ?string $column, ?callable $constraint = null): void
    {
        $this->model->setEagerLoading(true);
        $query = $this->model->{$include}();

        if ($this->model->getRelationType() === 'hasMany') {
            if ($constraint) {
                $constraint($query);
            }

            $relatingKey = $this->model->getRelatingKey();
            $ids = $models->ids();

            switch ($type) {
                case 'sum':
                    $results = $query->whereIn($relatingKey, $ids)->sumBy($relatingKey, $column)->all();
                    $attrSuffix = '_sum_' . $column;
                    $resultKey = 'sum_' . $column;
                    $defaultValue = null;

                    break;
                case 'avg':
                    $results = $query->whereIn($relatingKey, $ids)->avgBy($relatingKey, $column)->all();
                    $attrSuffix = '_avg_' . $column;
                    $resultKey = 'avg_' . $column;
                    $defaultValue = null;

                    break;
                case 'min':
                    $results = $query->whereIn($relatingKey, $ids)->minBy($relatingKey, $column)->all();
                    $attrSuffix = '_min_' . $column;
                    $resultKey = 'min_' . $column;
                    $defaultValue = null;

                    break;
                case 'max':
                    $results = $query->whereIn($relatingKey, $ids)->maxBy($relatingKey, $column)->all();
                    $attrSuffix = '_max_' . $column;
                    $resultKey = 'max_' . $column;
                    $defaultValue = null;

                    break;
            }

            $this->model->setEagerLoading(false);

            foreach ($models as $model) {
                $found = false;
                foreach ($results as $result) {
                    if ($result->{$relatingKey} === $model->{$model->getPrimaryKey()}) {
                        $model->{$include . $attrSuffix} = $result->{$resultKey};
                        $found = true;

                        break;
                    }
                }

                if (! $found) {
                    $model->{$include . $attrSuffix} = $defaultValue;
                }
            }
        }
    }

    /**
     * Get IDs for relationship query based on relation type
     */
    protected function getRelationIds(Collection $models, string $relationType): array
    {
        if (in_array($relationType, ['hasOne', 'pivot', 'hasManyThrough'])) {
            return $models->ids();
        }

        // hasMany and belongsTo
        $ids = $models->column($this->model->getRelatingForeignKey());

        return array_unique($ids);
    }

    /**
     * Get pivot key name based on relation type
     */
    protected function getPivotKeyName(?string $relationType): ?string
    {
        if ($relationType === 'pivot') {
            return $this->model->getPivotTable() . '.' . $this->model->getRelatingKey();
        }

        if ($relationType === 'hasManyThrough') {
            return $this->model->getRelatingKey();
        }

        return null;
    }

    /**
     * Set relation results on models
     */
    protected function setRelationResults(Collection $models, Collection $children, string $relation): void
    {
        foreach ($models as $model) {
            switch ($this->model->getRelationType()) {
                case 'hasOne':
                    $model->setAttribute($relation, $children->first([$this->model->getRelatingForeignKey() => $model->{$this->model->getPrimaryKey()}]));

                    break;
                case 'belongsTo':
                    $model->setAttribute($relation, $children->find($model->{$this->model->getRelatingForeignKey()}));

                    break;
                case 'hasMany':
                case 'hasManyThrough':
                case 'pivot':
                    $model->setAttribute($relation, $children->filter(function ($child) use ($model) {
                        return $child->{$this->model->getRelatingKey()} === $model->{$this->model->getPrimaryKey()};
                    }));

                    break;
            }

            $model->markRelationLoaded($relation);
        }
    }

    /**
     * Load nested relations
     */
    protected function loadNestedRelations(Collection $models, string $include, string $relation): void
    {
        $relations = substr($include, strlen($relation) + 1);

        if ($relations) {
            $items = $models->column($relation);

            if ($items) {
                $normalizedItems = [];

                foreach ($items as $item) {
                    if ($item instanceof Collection) {
                        $normalizedItems += $item->getItems();
                    } else {
                        $normalizedItems[] = $item;
                    }
                }

                $collection = new Collection($normalizedItems);
                $collection->load($relations);
            }
        }
    }
}
