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
     * @var array Relations to inlcude.
     */
    protected $includes;

    /**
     * @var array Relations to inlcude.
     */
    protected $countIncludes;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model, $model->getConnection());
    }

    public function with(string ...$includes): self
    {
        $this->includes = $includes;

        return $this;
    }

    public function withCount(string ...$includes): self
    {
        $this->countIncludes = $includes;

        return $this;
    }

    /**
     * Eager load all relations for a collection.
     * 
     * @param Collection $models
     */
    public function eagerLoadRelations(Collection $models)
    {
        foreach ($this->includes as $include) {

            $relation = explode('.', $include)[0];

            // Load relation only if the models has no such relation
            if (!$models->columnExists($relation)) {
                if (!method_exists($this->model, $relation)) {
                    throw new \Exception("Trying to eager load `{$relation}` but no relationship has been defined.");
                }

                $pivotKeyName = null;
                $query = $this->model->{$relation}();

                // if($this->model->getRelationType() !== 'pivot') {
                $query->resetWhere();
                $query->resetBindings();
                // }

                if ($this->model->getRelationType() === 'hasOne') {
                    $ids = $models->getKeys();
                } elseif ($this->model->getRelationType() === 'pivot') {
                    $ids = $models->getKeys();
                    $pivotKeyName = $this->model->getPivotTable() . '.' . $this->model->getRelatingKey();
                } elseif ($this->model->getRelationType() === 'hasManyThrough') {
                    $ids = $models->getKeys();
                    $pivotKeyName = $this->model->getRelatingKey();
                } else { // hasMany and belongsTo
                    $ids = $models->getByColumn($this->model->getRelatingForeignKey());
                    $ids = array_unique($ids);
                }

                $children = $query->whereIn($pivotKeyName ?? $this->model->getRelatingKey(), $ids)->all();

                foreach ($models as $model) {
                    if ($this->model->getRelationType() === 'hasOne') {
                        $model->setAttribute($relation, $children->getItemWherecolumn($this->model->getRelatingForeignKey(), $model->{$this->model->getPrimarykey()}));
                    } elseif ($this->model->getRelationType() === 'belongsTo') {
                        $model->setAttribute($relation, $children->getByKey($model->{$this->model->getRelatingForeignKey()}));
                        continue;
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
                }
            }

            // load nested relations for the models
            $relations = substr($include, strlen($relation) + 1);

            if ($relations) {
                $items = $models->getByColumn($relation);

                if ($items) {
                    $normalizedItems = [];

                    foreach ($items as $item) {
                        $normalizedItems += $item->getItems();
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
        foreach ($this->countIncludes as $include) {
            $query = $this->model->{$include}();
            $query->resetWhere();
            $query->resetBindings();

            if ($this->model->getRelationType() === 'hasMany') {
                $modelClass = $this->model->getRelatingModel();
                $table = (new $modelClass)->getTableName();
                $connection = $this->model->getConnection();
                $counts = $connection->table($table)->whereIn($this->model->getRelatingKey(), $models->getKeys())->countBy($this->model->getRelatingKey());

                foreach ($models as $model) {
                    foreach ($counts as $count) {
                        if ($count->{$this->model->getRelatingKey()} === $model->{$model->getPrimaryKey()}) {
                            $model->{$include . '_count'} = $count->num;
                        }
                    }
                }
            }
        }
    }

    public function hydrate(array $items)
    {
        $models = [];
        $modelClass = get_class($this->model);

        foreach ($items as $item) {
            $model = new $modelClass;
            $model->setAttributes((array) $item);
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

    public function hydrateItem(array $attributes)
    {
        $this->model->setAttributes($attributes);

        $collection = new Collection($this->model);

        if ($this->includes) {
            $this->eagerLoadRelations($collection);
        }

        if ($this->countIncludes) {
            $collection->loadCount(...$this->countIncludes);
        }

        return $this->model;
    }
}
