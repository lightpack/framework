<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\DB;
use Lightpack\Database\Query\Query;
use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Lucid\Pivot;

class RelationHandler
{
    /**
     * @var Model The parent model
     */
    protected $model;

    /**
     * @var string Current relation type
     */
    protected $relationType;

    /**
     * @var string Related model class
     */
    protected $relatedModel;

    /**
     * @var string Key used for relation
     */
    protected $relationKey;

    /**
     * @var string Foreign key for relation
     */
    protected $foreignKey;

    /**
     * @var string Pivot table for many-to-many
     */
    protected $pivotTable;

    /**
     * @var bool Is eager loading
     */
    protected $isEagerLoading = false;

    /**
     * @var array Cached relation results
     */
    protected $cache = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Set eager loading state
     */
    public function setEagerLoading(bool $state): void
    {
        $this->isEagerLoading = $state;
    }

    /**
     * Define a one-to-one relationship
     */
    public function hasOne(string $model, string $foreignKey): Query
    {
        $this->relationType = 'hasOne';
        $this->relationKey = $foreignKey;
        $this->foreignKey = $foreignKey;
        $this->relatedModel = $model;

        $model = $this->getConnection()->model($model);

        if ($this->isEagerLoading) {
            return $model::query();
        }

        return $model::query()->where($foreignKey, '=', $this->model->{$this->model->getPrimaryKey()});
    }

    /**
     * Define a one-to-many relationship
     */
    public function hasMany(string $model, string $foreignKey): Query
    {
        $this->relationType = 'hasMany';
        $this->relationKey = $foreignKey;
        $this->foreignKey = $this->model->getPrimaryKey();
        $this->relatedModel = $model;

        $model = $this->getConnection()->model($model);

        if ($this->isEagerLoading) {
            return $model::query();
        }

        return $model::query()->where($foreignKey, '=', $this->model->{$this->model->getPrimaryKey()});
    }

    /**
     * Define an inverse one-to-one or one-to-many relationship
     */
    public function belongsTo(string $model, string $foreignKey): Query
    {
        $model = $this->getConnection()->model($model);
        $this->relationType = 'belongsTo';
        $this->relationKey = $model->getPrimaryKey();
        $this->foreignKey = $foreignKey;
        $this->relatedModel = $model;

        if ($this->isEagerLoading) {
            return $model::query();
        }

        return $model::query()->where($this->model->getPrimaryKey(), '=', $this->model->{$foreignKey});
    }

    /**
     * Define a many-to-many relationship
     */
    public function pivot(string $model, string $pivotTable, string $foreignKey, string $associateKey): Pivot
    {
        $this->relationType = 'pivot';
        $this->relationKey = $foreignKey;
        $this->foreignKey = $foreignKey;
        $this->relatedModel = $model;
        $this->pivotTable = $pivotTable;

        $modelInstance = $this->getConnection()->model($model);
        $tableName = $modelInstance->getTableName();
        $pivot = new Pivot($modelInstance, $this->model, $pivotTable, $foreignKey, $associateKey);

        $pivot
            ->from($tableName)
            ->select("$tableName.*", "$pivotTable.$foreignKey")
            ->join($pivotTable, "$tableName.{$modelInstance->getPrimaryKey()}", "$pivotTable.$associateKey");

        if ($this->isEagerLoading) {
            return $pivot;
        }

        return $pivot->where("$pivotTable.$foreignKey", '=', $this->model->{$this->model->getPrimaryKey()});
    }

    /**
     * Define a has-one-through relationship
     */
    public function hasOneThrough(string $model, string $through, string $throughKey, string $foreignKey): Query
    {
        return $this->throughRelation('hasOneThrough', $model, $through, $throughKey, $foreignKey);
    }

    /**
     * Define a has-many-through relationship
     */
    public function hasManyThrough(string $model, string $through, string $throughKey, string $foreignKey): Query
    {
        return $this->throughRelation('hasManyThrough', $model, $through, $throughKey, $foreignKey);
    }

    /**
     * Internal helper for through relationships.
     */
    protected function throughRelation(string $relationType, string $model, string $through, string $throughKey, string $foreignKey): Query
    {
        $this->relationType = $relationType;
        $this->foreignKey = $throughKey;
        $this->relatedModel = $model;

        $modelInstance = $this->getConnection()->model($model);
        $throughInstance = $this->getConnection()->model($through);
        $this->relationKey = $throughKey;
        $modelTable = $modelInstance->getTableName();
        $throughTable = $throughInstance->getTableName();

        $query = $modelInstance
            ->query()
            ->from($modelTable)
            ->select("$modelTable.*", "$throughTable.$throughKey")
            ->join($throughTable, "$modelTable.$foreignKey", "$throughTable.{$throughInstance->getPrimaryKey()}");

        if ($this->isEagerLoading) {
            return $query;
        }

        return $query->where("$throughTable.$throughKey", '=', $this->model->{$this->model->getPrimaryKey()});
    }

    /**
     * Polymorphic belongs-to: Comment -> Post|Video
     * 
     * Accepts a list of model class names. Internally builds the morph map.
     * Usage: return $this->morphTo([PostModel::class, VideoModel::class]);
     */
    public function morphTo(array $models): ?Model
    {
        $type = $this->model->morph_type;
        $id = $this->model->morph_id;

        // Build morph map: ['posts' => PostModel::class, ...]
        $map = [];
        foreach ($models as $modelClass) {
            $table = (new $modelClass)->getTableName();
            $map[$table] = $modelClass;
        }

        if (!isset($map[$type])) {
            return null;
        }

        $related = new $map[$type];
        return $related->find($id);
    }

    /**
     * Polymorphic "many": e.g. Post -> many Comments
     */
    public function morphMany(string $model, string $morphType): Query
    {
        return $this->hasMany($model, 'morph_id')
            ->where('morph_type', $morphType);
    }

    /**
     * Polymorphic "one": e.g. User -> one Avatar
     */
    public function morphOne(string $model, string $morphType): Query
    {
        return $this->hasOne($model, 'morph_id')
            ->where('morph_type', $morphType);
    }

    /**
     * Polymorphic many-to-many: e.g. Post -> many Tags (through tag_models)
     * 
     * Note: Pivot table MUST have columns: morph_id, morph_type, and the related model's PK column.
     * Example: tag_models table has: tag_id, morph_id, morph_type
     */
    public function morphToMany(string $model, string $pivotTable, string $associateKey): PolymorphicPivot
    {
        $this->relationType = 'morphToMany';
        $this->relationKey = 'morph_id';
        $this->foreignKey = 'morph_id';
        $this->relatedModel = $model;
        $this->pivotTable = $pivotTable;

        $modelInstance = $this->getConnection()->model($model);
        $tableName = $modelInstance->getTableName();
        $morphType = $this->model->getTableName();
        
        $pivot = new PolymorphicPivot(
            $modelInstance,
            $this->model,
            $pivotTable,
            $associateKey,
            $morphType
        );

        $pivot
            ->from($tableName)
            ->select("$tableName.*", "$pivotTable.morph_id")
            ->join($pivotTable, "$tableName.{$modelInstance->getPrimaryKey()}", "$pivotTable.$associateKey")
            ->where("$pivotTable.morph_type", '=', $morphType);

        if ($this->isEagerLoading) {
            return $pivot;
        }

        return $pivot->where("$pivotTable.morph_id", '=', $this->model->{$this->model->getPrimaryKey()});
    }

    /**
     * Inverse polymorphic many-to-many: e.g. Tag -> many Posts (through tag_morphs)
     * 
     * Note: Pivot table MUST have columns: morph_id, morph_type, and the related model's PK column.
     * Example: tag_morphs table has: tag_id, morph_id, morph_type
     */
    public function morphedByMany(string $model, string $pivotTable, string $associateKey): PolymorphicPivot
    {
        $this->relationType = 'morphedByMany';
        $this->relationKey = 'morph_id';
        $this->foreignKey = 'morph_id';
        $this->relatedModel = $model;
        $this->pivotTable = $pivotTable;

        $modelInstance = $this->getConnection()->model($model);
        $tableName = $modelInstance->getTableName();
        $morphType = $tableName; // Auto-detect from related model's table
        
        $pivot = new PolymorphicPivot(
            $modelInstance,
            $this->model,
            $pivotTable,
            $associateKey,
            $morphType
        );

        $pivot
            ->from($tableName)
            ->select("$tableName.*", "$pivotTable.$associateKey")
            ->join($pivotTable, "$tableName.{$modelInstance->getPrimaryKey()}", "$pivotTable.morph_id")
            ->where("$pivotTable.morph_type", '=', $morphType);

        if ($this->isEagerLoading) {
            return $pivot;
        }

        return $pivot->where("$pivotTable.$associateKey", '=', $this->model->{$this->model->getPrimaryKey()});
    }

    /**
     * Get cached relation result
     */
    public function getFromCache(string $relation)
    {
        return $this->cache[$relation] ?? null;
    }

    /**
     * Cache relation result
     */
    public function cache(string $relation, $value): void
    {
        $this->cache[$relation] = $value;
    }

    /**
     * Get all cached relations
     */
    public function getCachedRelations(): array
    {
        return $this->cache;
    }

    /**
     * Get current relation type
     */
    public function getRelationType(): string
    {
        return $this->relationType;
    }

    /**
     * Get relation key
     */
    public function getRelationKey(): string
    {
        return $this->relationKey;
    }

    /**
     * Get foreign key
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * Get related model
     */
    public function getRelatedModel(): string
    {
        return $this->relatedModel;
    }

    /**
     * Get pivot table
     */
    public function getPivotTable(): ?string
    {
        return $this->pivotTable;
    }

    /**
     * Get database connection
     */
    protected function getConnection(): DB
    {
        return $this->model->getConnection();
    }
}
