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
            if(!$models->columnExists($relation)) {
                if (!method_exists($this->model, $relation)) {
                    throw new \Exception("Trying to eager load `{$relation}` but no relationship has been defined.");
                }
    
                $query = $this->model->{$relation}();

                $query->resetWhere();
                $query->resetBindings();
    
                $pivotKeyName = null;

                if ($this->model->getRelationType() === 'hasOne') {
                    $ids = $models->getKeys();
                } elseif ($this->model->getRelationType() === 'pivot') {
                    $ids = $models->getKeys();
                    $pivotKeyName = $this->model->getPivotTable() . '.' . $this->model->getRelatingKey();
                } else { // hasMany and belongsTo
                    $ids = $models->getByColumn($this->model->getRelatingForeignKey());
                    $ids = array_unique($ids);
                }
    
                if (empty($ids)) {
                    continue;
                }

                $children = $query->whereIn($pivotKeyName ?? $this->model->getRelatingKey(), $ids)->all();

                foreach ($models as $model) {
                    if($model->hasAttribute($relation)) {
                        continue;
                    }
    
                    if ($this->model->getRelationType() === 'hasOne') {
                        $model->setAttribute($relation, $children->getItemWherecolumn($this->model->getRelatingForeignKey(), $model->{$this->model->getPrimarykey()}));
                    } elseif ($this->model->getRelationType() === 'belongsTo') {
                        $model->setAttribute($relation, $children->getByKey($model->{$this->model->getRelatingForeignKey()}));
                        continue;
                    } elseif($this->model->getRelationType() === 'hasMany') {
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
                    if ($items[0] instanceof Collection) {
                        $normalizedItems = [];

                        foreach ($items as $item) {
                            $normalizedItems += $item->getItems();
                        }

                        $collection = new Collection($normalizedItems);
                    } else {
                        $collection = new Collection($items);
                    }

                    $collection->load($relations);
                }
            }
        }
    }

    /**
     * Eager load all relations for a model.
     * 
     * @param Model $models
     */
    public function eagerLoadRelation(Model $model)
    {
        foreach ($this->includes as $include) {
            if (!method_exists($this->model, $include)) {
                throw new \Exception("Trying to eager load `{$include}` but no relationship has been defined.");
            }

            $query = $this->model->{$include}();

            $query->resetWhere();
            $query->resetBindings();

            if ($this->model->getRelationType() === 'pivot') {
                $keyName = $this->model->getPivotTable() . '.' . $this->model->getRelatingKey();
            }

            if ($this->model->getRelationType() === 'hasOne' || $this->model->getRelationType() === 'belongsTo') {
                $id = $model->{$this->model->getRelatingForeignKey()};
                $children = $query->where($keyName ?? $this->model->getRelatingKey(), '=', $id)->one();
                $model->setAttribute($include, $children);
                continue;
            }

            $id = $model->{$this->model->getPrimaryKey()};
            $children = $query->where($keyName ?? $this->model->getRelatingKey(), '=', $id)->all();
            $model->setAttribute($include, $children);
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
                $counts = (new Query($table))->whereIn($this->model->getRelatingKey(), $models->getKeys())->groupCount($this->model->getRelatingKey());

                foreach ($models as $model) {
                    foreach ($counts as $count) {
                        if ($count->{$this->model->getRelatingKey()} === $model->id) {
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

        if ($this->includes) {
            $collection = new Collection($this->model);
            $collection->load(...$this->includes);
        }

        return $this->model;
    }
}
