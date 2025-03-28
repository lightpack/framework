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
        $this->countIncludes = $includes;
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
            if (!is_string($key)) {
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

        if (!method_exists($this->model, $relation)) {
            throw new \Exception("Trying to eager load `{$relation}` but no relationship has been defined.");
        }

        $this->model->setEagerLoading(true);
        $query = $this->model->{$relation}();
        $ids = $this->getRelationIds($models, $this->model->getRelationType());
        $pivotKeyName = $this->getPivotKeyName($this->model->getRelationType());

        if (!$ids) {
            return;
        }

        if ($constraint) {
            $constraint($query);
        }

        $children = $query->whereIn($pivotKeyName ?? $this->model->getRelatingKey(), $ids)->all();
        $this->model->setEagerLoading(false);

        $this->setRelationResults($models, $children, $relation);
        $this->loadNestedRelations($models, $include, $relation);
    }

    /**
     * Load count for a single relation
     */
    protected function loadRelationCount(Collection $models, $key, $value): void
    {
        $constraint = null;
        $include = $value;

        if (is_callable($value)) {
            if (!is_string($key)) {
                throw new \Exception("Relation key must be a string.");
            }
            $constraint = $value;
            $include = $key;
        }

        $this->model->setEagerLoading(true);
        $query = $this->model->{$include}();

        if ($this->model->getRelationType() === 'hasMany') {
            if ($constraint) {
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
