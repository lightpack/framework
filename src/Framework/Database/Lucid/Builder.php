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
     * @var array Relations to include.
     */
    protected $includes;

    /**
     * @var array Relations to include.
     */
    protected $countIncludes;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model->getTableName(), $model->getConnection());
    }

    protected function executeBeforeFetchHookForModel()
    {
        if($this->model) {
            $this->model->beforeFetch($this);
        }
    }

    public function all()
    {
        $this->executeBeforeFetchHookForModel();
        $results = parent::all();
        return $this->hydrate($results);
    }

    public function one()
    {
        $this->executeBeforeFetchHookForModel();
        $result = parent::one();
        
        if ($result) {
            $result = $this->hydrateItem((array) $result);
        }

        return $result;
    }

    public function column(string $column)
    {
        $this->executeBeforeFetchHookForModel();
        return parent::column($column);
    }

    /**
     * @param integer|null $limit
     * @param integer|null $page
     * @return \Lightpack\Database\Lucid\Pagination
     */
    public function paginate(int $limit = null, int $page = null)
    {
        $this->executeBeforeFetchHookForModel();
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

        if ($this->includes) {
            $this->eagerLoadRelations($models);
        }

        if ($this->countIncludes) {
            $this->eagerLoadRelationsCount($models);
        }

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

        if ($this->includes) {
            $this->eagerLoadRelations($collection);
        }

        if ($this->countIncludes) {
            $collection->loadCount(...$this->countIncludes);
        }

        return $model;
    }

    public function with(): self
    {
        $relations = func_get_args();

        if (is_array($relations[0])) {
            $this->includes = $relations[0];
        } else {
            $this->includes = $relations;
        }

        return $this;
    }

    public function withCount(): self
    {
        $args = func_get_args();

        if (is_array($args[0])) {
            $this->countIncludes = $args[0];
        } else {
            $this->countIncludes = $args;
        }

        return $this;
    }

    public function has(string $relation, string $operator = null, string $count = null, callable $constraint = null): self
    {
        if (!method_exists($this->model, $relation)) {
            throw new \Exception("Relation {$relation} does not exist.");
        }
        $relationQuery = $this->model->{$relation}();
        $relatingTable = $relationQuery->table;
        $relatingKey = $this->model->getRelatingKey();


        // we will apply count query
        if (!is_null($operator) && !is_null($count)) {
            $query = $this->model->getConnection()->table($relatingTable);

            $subQueryCallback = function ($q) use ($relatingTable, $relatingKey) {
                $q->from($relatingTable)
                    ->select('COUNT(*)')
                    ->whereRaw($this->model->getTableName() . '.' . $this->model->getPrimaryKey() . ' = ' . $relatingTable . '.' . $relatingKey);
            };

            $subQueryCallback($query);

            $bindings = [];

            if ($constraint) {
                $constraint($query);
                $bindings = $query->bindings;
            }

            $bindings[] = $count;
            return $this->whereRaw('(' . $query->toSql() . ')' . ' ' . $operator . ' ' . '?', $bindings);
        }

        // else we will apply 'where exists' clause
        $subQuery = function ($q) use ($relatingTable, $relatingKey, $constraint) {
            $q->from($relatingTable)
                ->whereRaw($this->model->getTableName() . '.' . $this->model->getPrimaryKey() . ' = ' . $relatingTable . '.' . $relatingKey);

            if ($constraint) {
                $constraint($q);
            }
        };

        return $this->whereExists($subQuery);
    }

    public function whereHas(string $relation, callable $constraint, string $operator = null, string $count = null): self
    {
        return $this->has($relation, $operator, $count, $constraint);
    }

    /**
     * Eager load all relations for a collection.
     * 
     * @param Collection $models
     */
    public function eagerLoadRelations(Collection $models)
    {
        foreach ($this->includes as $key => $value) {
            if (is_callable($value)) {
                if (!is_string($key)) {
                    throw new \Exception("Relation key must be a string.");
                }

                $constraint = $value;
                $include = $key;
            }

            if (is_string($value)) {
                $include = $value;
            }

            $relation = explode('.', $include)[0];

            // Load relation only if the models has no such relation
            if (!$models->any($relation)) {
                if (!method_exists($this->model, $relation)) {
                    throw new \Exception("Trying to eager load `{$relation}` but no relationship has been defined.");
                }

                $pivotKeyName = null;
                
                $this->model->setEagerLoading(true);
                $query = $this->model->{$relation}();

                if ($this->model->getRelationType() === 'hasOne') {
                    $ids = $models->ids();
                } elseif ($this->model->getRelationType() === 'pivot') {
                    $ids = $models->ids();
                    $pivotKeyName = $this->model->getPivotTable() . '.' . $this->model->getRelatingKey();
                } elseif ($this->model->getRelationType() === 'hasManyThrough') {
                    $ids = $models->ids();
                    $pivotKeyName = $this->model->getRelatingKey();
                } else { // hasMany and belongsTo
                    $ids = $models->column($this->model->getRelatingForeignKey());
                    $ids = array_unique($ids);
                }

                if(!$ids) {
                    continue;
                }

                if($constraint ?? false) {
                    $constraint($query);
                }

                $children = $query->whereIn($pivotKeyName ?? $this->model->getRelatingKey(), $ids)->all();
                $this->model->setEagerLoading(false);

                foreach ($models as $model) {
                    if ($this->model->getRelationType() === 'hasOne') {
                        $model->setAttribute($relation, $children->first([$this->model->getRelatingForeignKey() => $model->{$this->model->getPrimaryKey()}]));
                    } elseif($this->model->getRelationType() === 'belongsTo') {
                        $model->setAttribute($relation, $children->find($model->{$this->model->getRelatingForeignKey()}));
                    } elseif ($this->model->getRelationType() === 'hasMany') {
                        $model->setAttribute($relation, $children->filter(function ($child) use ($model) {
                            return $child->{$this->model->getRelatingKey()} === $model->{$this->model->getPrimaryKey()};
                        }));
                    } elseif ($this->model->getRelationType() === 'hasManyThrough') {
                        $model->setAttribute($relation, $children->filter(function ($child) use ($model) {
                            return $child->{$this->model->getRelatingKey()} === $model->{$this->model->getPrimaryKey()};
                        }));
                    } else { // pivot table relation
                        $model->setAttribute($relation, $children->filter(function ($child) use ($model) {
                            return $child->{$this->model->getRelatingKey()} === $model->{$this->model->getPrimaryKey()};
                        }));
                    }
                    
                    // Mark the relation as loaded
                    $model->markRelationLoaded($relation);
                }
            }

            // load nested relations for the models
            $relations = substr($include, strlen($relation) + 1);

            if ($relations) {
                $items = $models->column($relation);

                if ($items) {
                    $normalizedItems = [];

                    foreach ($items as $item) {
                        if($item instanceof Collection) {
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

    /**
     * Eager load all relations count.
     * 
     * @param Collection $models
     * @return array
     */
    public function eagerLoadRelationsCount(Collection $models)
    {
        if($models->isEmpty()) {
            return;
        }

        foreach ($this->countIncludes as $key => $value) {
            if (is_callable($value)) {
                if (!is_string($key)) {
                    throw new \Exception("Relation key must be a string.");
                }

                $constraint = $value;
                $include = $key;
            }

            if (is_string($value)) {
                $include = $value;
            }

            $this->model->setEagerLoading(true);
            $query = $this->model->{$include}();
            // $query->resetWhere();
            // $query->resetBindings();

            if ($this->model->getRelationType() === 'hasMany') {
                if($constraint ?? false) {
                    $constraint($query);
                }

                $counts = $query->whereIn($this->model->getRelatingKey(), $models->ids())->countBy($this->model->getRelatingKey());
                $this->model->setEagerLoading(false);

                foreach ($models as $model) {
                    foreach ($counts as $count) {
                        if ($count->{$this->model->getRelatingKey()} === $model->{$model->getPrimaryKey()}) {
                            $model->{$include . '_count'} = $count->num;
                        }
                    }

                    if (!$model->hasAttribute($include . '_count')) {
                        $model->{$include . '_count'} = 0;
                    }
                }
            }
        }
    }
}
