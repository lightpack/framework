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
     * Eager load all relations.
     * 
     * @param Collection $models
     * @return array
     */
    public function eagerLoadRelations(Collection $models)
    {
        foreach($this->includes as $include) {
            if(!method_exists($this->model, $include)) {
                throw new \Exception("Trying to eager load `{$include}` but no relationship has been defined.");
            }
            
            $query = $this->model->{$include}();

            $query->resetWhere();
            $query->resetBindings();

            if($this->model->getRelationType() === 'hasOne') {
                $ids = $models->getKeys();
            } elseif($this->model->getRelationType() === 'pivot') {
                $ids = $models->getKeys();
                $keyName = $this->model->getPivotTable() . '.' . $this->model->getRelatingKey();
            } else {
                $ids = $models->getByColumn($this->model->getRelatingForeignKey());
                $ids = array_unique($ids);
            }

            if(empty($ids)) {
                continue;
            }
            
            $children = $query->whereIn($keyName ?? $this->model->getRelatingKey(), $ids)->all();

            foreach($models as $model) {
                if($this->model->getRelationType() === 'hasOne') {
                    $model->setAttribute($include, $children->getByKey($model->{$this->model->getRelatingForeignKey()}));
                    continue;
                }

                if($this->model->getRelationType() === 'belongsTo') {
                    $model->setAttribute($include, $children->getByKey($model->{$this->model->getRelatingForeignKey()}));
                    continue;
                } 

                
                $model->setAttribute($include, $children->filter(function($child) use ($model) {
                    return $child->{$this->model->getRelatingKey()} === $model->{$this->model->getPrimaryKey()};
                }));
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
        foreach($this->countIncludes as $include) {
            $query = $this->model->{$include}();
            $query->resetWhere();
            $query->resetBindings();

            if($this->model->getRelationType() === 'hasMany') {
                $modelClass = $this->model->getRelatingModel();
                $table = (new $modelClass)->getTableName();
                $counts = (new Query($table))->whereIn($this->model->getRelatingKey(), $models->getKeys())->groupCount($this->model->getRelatingKey());

                foreach($models as $model) {
                    foreach($counts as $count) {
                        if($count->{$this->model->getRelatingKey()} === $model->id) {
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
            $primaryKey = $model->getPrimaryKey();
            $models[$model->{$primaryKey}] = $model;
        }

        $models = new Collection($models);

        if($this->includes) {
            $this->eagerLoadRelations($models);
        }

        if($this->countIncludes)
        {
            $this->eagerLoadRelationsCount($models);
        }

        return $models;
    }

    public function hydrateItem(array $attributes)
    {
        $this->model->setAttributes($attributes);
        
        return $this->model;
    }
}