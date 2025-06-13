<?php

namespace Lightpack\Database\Lucid;

use Exception;
use JsonSerializable;
use Lightpack\Database\DB;
use Lightpack\Database\Query\Query;
use Lightpack\Exceptions\RecordNotFoundException;
use Lightpack\Database\Lucid\AttributeHandler;
use Lightpack\Database\Lucid\RelationHandler;

class Model implements JsonSerializable
{
    /** 
     * @var string Database table name 
     */
    protected $table;

    /** 
     * @var string Table primary key 
     */
    protected $primaryKey = 'id';

    /** 
     * @var \Lightpack\Database\DB
     */
    protected $connection;

    /**
     * @var bool Timestamps
     */
    protected $timestamps = false;

    /**
     * @var array Attributes to be hidden for serialization or array conversion.
     */
    protected $hidden = [];

    /**
     * @var array The attributes that should be cast.
     */
    protected $casts = [];

    /**
     * @var AttributeHandler
     */
    protected $attributes;

    /**
     * @var RelationHandler
     */
    protected $relations;

    /**
     * @var bool Enable strict relation loading
     */
    protected $strictMode = false;

    /**
     * @var array Relations that can be lazy loaded even in strict mode
     */
    protected $allowedLazyRelations = [];

    /**
     * @var array Currently loaded relations
     */
    protected $loadedRelations = [];

    /**
     * The transformer(s) for this model.
     * 
     * Can be:
     * - string: Single transformer class (e.g., UserTransformer::class)
     * - array: Multiple transformers for different contexts
     *         [
     *             'api' => UserApiTransformer::class,
     *             'view' => UserViewTransformer::class
     *         ]
     * - null: No transformation
     * 
     * @var string|array|null
     */
    protected $transformer = null;

    /**
     * Constructor.
     *
     * @param [int|string] $id
     */
    public function __construct($id = null)
    {
        $this->attributes = new AttributeHandler();
        $this->attributes->setHidden($this->hidden);
        $this->attributes->setTimestamps($this->timestamps);
        $this->attributes->setCasts($this->casts);

        $this->relations = new RelationHandler($this);

        if ($id) {
            $this->find($id);
        }
    }

    /**
     * Sets the model properties.
     */
    public function __set($column, $value)
    {
        if (!method_exists($this, $column)) {
            $this->attributes->set($column, $value);
        }
    }

    /**
     * Returns a model property or executes a relation
     * method if present.
     */
    public function __get(string $key)
    {
        // Check attributes first
        if ($this->attributes->has($key)) {
            return $this->attributes->get($key);
        }

        // Check for relation method
        if (!method_exists($this, $key)) {
            return $this->attributes->get($key);
        }

        // Check if relation is allowed to be lazy loaded
        if ($this->strictMode && !in_array($key, $this->allowedLazyRelations)) {
            throw new \RuntimeException(
                sprintf(
                    "Strict Mode: Relation '%s' on %s must be eager loaded.",
                    $key,
                    get_class($this)
                )
            );
        }

        // Check relation cache
        if ($cached = $this->relations->getFromCache($key)) {
            return $cached;
        }

        // Execute relation
        $query = $this->{$key}();
        $result = $this->relations->getRelationType() === 'hasMany' ||
            $this->relations->getRelationType() === 'pivot' ||
            $this->relations->getRelationType() === 'hasManyThrough'
            ? $query->all()
            : $query->one();

        $this->relations->cache($key, $result);
        return $result;
    }

    public function setConnection(DB $connection): void
    {
        $this->connection = $connection;
    }

    public function getConnection(): DB
    {
        return $this->connection ?? app('db');
    }

    public function setEagerLoading(bool $flag)
    {
        $this->relations->setEagerLoading($flag);
    }

    /**
     * One-to-one: e.g. User -> Profile
     */
    public function hasOne(string $model, string $foreignKey): Query
    {
        return $this->relations->hasOne($model, $foreignKey);
    }

    /**
     * One-to-many: e.g. Post -> Comments
     */
    public function hasMany(string $model, string $foreignKey): Query
    {
        return $this->relations->hasMany($model, $foreignKey);
    }

    /**
     * Inverse: e.g. Comment -> Post
     */
    public function belongsTo(string $model, string $foreignKey): Query
    {
        return $this->relations->belongsTo($model, $foreignKey);
    }

    /**
     * Many-to-many (pivot): e.g. User -> Roles, Role -> Users
     */
    public function pivot(string $model, string $pivotTable, string $foreignKey, string $associateKey): Pivot
    {
        return $this->relations->pivot($model, $pivotTable, $foreignKey, $associateKey);
    }

    /**
     * Has-many-through: e.g. Country -> User -> Posts
     */
    public function hasManyThrough(string $model, string $through, string $throughKey, string $foreignKey): Query
    {
        return $this->relations->hasManyThrough($model, $through, $throughKey, $foreignKey);
    }

    /**
     * Polymorphic inverse: e.g. Comment -> Post|Video
     */
    public function morphTo(array $map)
    {
        return $this->relations->morphTo($map);
    }

    /**
     * Polymorphic "many": e.g. Post -> many Comments
     */
    public function morphMany(string $model)
    {
        return $this->relations->morphMany($model, $this->table);
    }

    /**
     * Polymorphic "one": e.g. User -> one Avatar
     */
    public function morphOne(string $model)
    {
        return $this->relations->morphOne($model, $this->table);
    }

    public function find($id, bool $fail = true): self
    {
        $query = new Query($this->table, $this->getConnection());

        $this->applyScope($query);
        $data = $query->where($this->primaryKey, '=', $id)->one();

        if (!$data && $fail) {
            throw new RecordNotFoundException(
                sprintf('%s: No record found for ID = %d', get_called_class(), $id)
            );
        }

        if ($data) {
            $this->attributes->fillRaw((array)$data);
        }

        return $this;
    }

    public function save(): void
    {
        $primaryKeyValue = $this->attributes->get($this->primaryKey);
        $query = $this->query();

        $this->beforeSave($query);

        if ($primaryKeyValue !== null) {
            $this->update($query);
        } else {
            $this->insert($query);
        }

        $this->attributes->clearDirty(); // Clear modified state after save
        $this->afterSave();
    }

    public function delete($id = null)
    {
        if ($id) {
            $this->find($id);
        }

        if (!$this->attributes->get($this->primaryKey)) {
            return;
        }

        $query = $this->query();
        $this->beforeDelete($query);
        $query->where($this->primaryKey, '=', $this->attributes->get($this->primaryKey))->delete();
        $this->afterDelete();
    }

    public function lastInsertId()
    {
        return $this->getConnection()->lastInsertId();
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public static function query(): Builder
    {
        $model = new static;
        $builder = new Builder($model);
        $model->applyScope($builder);

        return $builder;
    }

    public static function filters(array $filters): Builder
    {
        $builder = self::query();
        $model = $builder->getModel();

        foreach ($filters as $key => $value) {
            $method = 'scope' . str()->camelize($key);

            if (method_exists($model, $method)) {
                $model->{$method}($builder, $value);
            }
        }

        return $builder;
    }

    protected function applyScope(Query $query)
    {
        // ...
    }

    protected function beforeSave()
    {
        // Hook method
    }

    protected function afterSave()
    {
        // Hook method
    }

    protected function beforeDelete()
    {
        // Hook method
    }

    protected function afterDelete()
    {
        // Hook method
    }

    protected function insert(Query $query)
    {
        $this->attributes->updateTimestamps(false);

        $result = $query->insert($this->attributes->toDatabaseArray());

        $this->attributes->set($this->primaryKey, $this->lastInsertId());

        return $result;
    }

    protected function update(Query $query)
    {
        $this->attributes->updateTimestamps();
        $data = $this->attributes->toDatabaseArray();
        unset($data[$this->primaryKey]);
        return $query->where($this->primaryKey, '=', $this->attributes->get($this->primaryKey))->update($data);
    }

    public function jsonSerialize(): mixed
    {
        return $this->attributes->toArray();
    }

    public function getRelationType()
    {
        return $this->relations->getRelationType();
    }

    public function getRelatingKey()
    {
        return $this->relations->getRelationKey();
    }

    public function getRelatingForeignKey()
    {
        return $this->relations->getForeignKey();
    }

    public function getRelatingModel()
    {
        return $this->relations->getRelatedModel();
    }

    public function getPivotTable()
    {
        return $this->relations->getPivotTable();
    }

    public function getAttributes()
    {
        return $this->attributes->all();
    }

    public function getAttribute($key, $default = null)
    {
        return $this->attributes->get($key, $default);
    }

    public function setAttributes(array $data)
    {
        $this->attributes->fillRaw($data);
    }

    public function hasAttribute(string $key)
    {
        return $this->attributes->has($key);
    }

    public function setAttribute($key, $value)
    {
        $this->attributes->set($key, $value);
    }

    public function load()
    {
        $relations = func_get_args();
        $items = new Collection($this);
        $items->load(...$relations);
    }

    public function loadCount()
    {
        $relations = func_get_args();
        $items = new Collection($this);
        $items->loadCount(...$relations);
    }

    public function toArray()
    {
        $data = $this->attributes->toArray();

        foreach ($data as $key => $value) {
            if (is_object($value)) {
                if ($value instanceof Collection || $value instanceof Model) {
                    $data[$key] = $value->toArray();
                }
            }
        }

        return $data;
    }

    public function getCachedModels()
    {
        return $this->relations->getCachedRelations();
    }

    public function clone(array $exclude = []): self
    {
        if (!$this->attributes->get($this->primaryKey)) {
            throw new \Exception('You cannot clone a non-existing model instance.');
        }

        $instance = new static;
        $exclude = array_merge($exclude, [$this->primaryKey, 'created_at', 'updated_at']);
        $data = $this->attributes->toArray();

        foreach ($data as $key => $value) {
            if (!in_array($key, $exclude)) {
                $instance->setAttribute($key, $value);
            }
        }

        return $instance;
    }

    /**
     * Track loaded relations
     */
    public function markRelationLoaded(string $relation): void
    {
        if (!in_array($relation, $this->loadedRelations)) {
            $this->loadedRelations[] = $relation;
        }
    }

    /**
     * Check if a relation is loaded
     */
    public function isRelationLoaded(string $relation): bool
    {
        return in_array($relation, $this->loadedRelations);
    }

    /**
     * Get list of loaded relations
     */
    public function getLoadedRelations(): array
    {
        return $this->loadedRelations;
    }

    /**
     * Transform the model using its transformer
     */
    public function transform(array $options = []): array
    {
        if (!$this->transformer) {
            throw new Exception('No transformer defined for model: ' . get_class($this));
        }

        // Single transformer case
        if (is_string($this->transformer)) {
            $transformer = new $this->transformer();
        }
        // Multiple transformers case
        else {
            $context = $options['context'];

            if (!isset($this->transformer[$context])) {
                $available = implode(', ', array_keys($this->transformer));
                throw new \RuntimeException(
                    "Invalid transformer context '{$context}' for " . get_class($this) .
                        ". Available contexts: {$available}"
                );
            }

            $transformer = new $this->transformer[$context]();
        }

        // Apply options
        if (isset($options['fields'])) {
            $transformer->fields($options['fields']);
        }

        if (isset($options['includes'])) {
            $transformer->includes($options['includes']);
        }

        return $transformer->transform($this);
    }

    /**
     * Check if model or specific attributes are in dirty state.
     */
    public function isDirty(?string $attribute = null): bool
    {
        return $this->attributes->isDirty($attribute);
    }

    /**
     * Get attributes in dirty state.
     */
    public function getDirty(): array
    {
        return $this->attributes->getDirty();
    }
}
