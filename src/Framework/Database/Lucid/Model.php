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
     * @var bool Timestamps: created_at and updated_at
     */
    protected $timestamps = false;

    /**
     * @var bool
     */
    protected $autoIncrements = true;

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
     * Has-one-through: e.g. Country -> User -> Profile
     */
    public function hasOneThrough(string $model, string $through, string $throughKey, string $foreignKey): Query
    {
        return $this->relations->hasOneThrough($model, $through, $throughKey, $foreignKey);
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

    /**
     * Polymorphic many-to-many: e.g. Post -> many Tags (through tag_models)
     * 
     * Note: Pivot table MUST have columns: morph_id, morph_type, and the related model's PK.
     */
    public function morphToMany(string $model, string $pivotTable, string $associateKey)
    {
        return $this->relations->morphToMany($model, $pivotTable, $associateKey);
    }

    /**
     * Inverse polymorphic many-to-many: e.g. Tag -> many Posts (through tag_models)
     * 
     * Note: Pivot table MUST have columns: morph_id, morph_type, and the related model's PK.
     */
    public function morphedByMany(string $model, string $pivotTable, string $morphType, string $associateKey)
    {
        return $this->relations->morphedByMany($model, $pivotTable, $morphType, $associateKey);
    }

    public function find($id, bool $fail = true): self
    {
        $query = new Query($this->table, $this->getConnection());

        $this->globalScope($query);
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

    /**
     * Save the current model instance to the database.
     *
     * - If the primary key is not set, inserts a new record.
     * - If the primary key is set, updates the existing record.
     * - Only reliable for models with auto-increment primary keys.
     * - For non-auto-increment PKs, use insert() and update() explicitly.
     * - Executes beforeSave() and afterSave() hooks for extensibility.
     *
     * @return void
     *
     * @throws \PDOException On database errors.
     */
    public function save(): void
    {
        $primaryKeyValue = $this->attributes->get($this->primaryKey);

        $this->beforeSave();

        if ($primaryKeyValue !== null) {
            $this->executeUpdate();
        } else {
            $this->executeInsert();
        }

        $this->attributes->clearDirty();
        $this->afterSave();
    }

    /**
     * Delete the current model instance from the database.
     *
     * If an $id is provided, attempts to find and delete the record with that primary key.
     * If no $id is provided, deletes the current instance (if it has a primary key value).
     *
     * - Returns true if a row was deleted, false if nothing was deleted (e.g., missing PK or record not found).
     * - Executes beforeDelete() and afterDelete() hooks for extensibility.
     * - Throws exceptions on database errors (e.g., constraint violations).
     *
     * @param mixed|null $id Optional primary key value to delete a specific record.
     * @return bool True if a row was deleted, false otherwise.
     *
     * @throws \Lightpack\Exceptions\RecordNotFoundException If $id is provided and not found.
     * @throws \PDOException On database errors.
     */
    public function delete($id = null): bool
    {
        if ($id) {
            $this->find($id);
        }

        if (!$this->attributes->get($this->primaryKey)) {
            return false;
        }

        $this->beforeDelete();
        $affected = self::query()->where($this->primaryKey, '=', $this->attributes->get($this->primaryKey))->delete();
        $this->afterDelete();

        return $affected === 1;
    }

    /**
     * Fetch a new instance using current model without mutating itself.
     * Returns null if the primary key is not set.
     */
    public function refetch(): ?static
    {
        $primaryKeyValue = $this->attributes->get($this->primaryKey);

        if ($primaryKeyValue === null) {
            return null;
        }

        return (new static)->find($primaryKeyValue);
    }

    /**
     * Get the last auto-incremented primary key value from the database connection.
     *
     * Used after insert operations to retrieve the generated primary key value
     * for models with auto-incrementing primary keys.
     *
     * @return null|int The last inserted primary key value.
     */
    public function lastInsertId(): ?int
    {
        if (!$this->autoIncrements) {
            return null;
        }

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
        $model->globalScope($builder);

        return $builder;
    }

    public static function queryWithoutScopes(): Builder
    {
        $model = new static;
        $builder = new Builder($model);

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

    protected function globalScope(Query $query)
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

    protected function beforeInsert()
    {
        // Hook method
    }

    protected function afterInsert()
    {
        // Hook method
    }

    protected function beforeUpdate()
    {
        // Hook method
    }

    protected function afterUpdate()
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

    /**
     * Insert the current model instance into the database.
     * - For non-auto-increment PKs, explicitly set the primary key before calling.
     * - Executes beforeInsert() and afterInsert() hooks for extensibility.
     *
     * @return void
     *
     * @throws \PDOException On database errors.
     */
    public function insert(): void
    {
        $this->beforeInsert();

        $this->executeInsert();

        $this->attributes->clearDirty();
        $this->afterInsert();
    }

    /**
     * Updates the row matching the model's primary key.
     * - Executes beforeUpdate() and afterUpdate() hooks for extensibility.
     *
     * @return bool True if a row was updated, false otherwise.
     *
     * @throws \PDOException On database errors.
     * @throws \RuntimeException If the primary key is not set.
     */
    public function update(): bool
    {
        $this->beforeUpdate();

        $affectedRows = $this->executeUpdate();

        $this->attributes->clearDirty();
        $this->afterUpdate();

        return $affectedRows == 1;
    }

    protected function executeInsert(): void
    {
        $this->attributes->updateTimestamps(false);

        // Error: Manual PK required for non-auto-incrementing models
        if (!$this->autoIncrements && $this->attributes->get($this->primaryKey) === null) {
            throw new \RuntimeException('Insert failed: This model does not use an auto-incrementing primary key. You must assign a primary key value before saving.');
        }

        $result = self::queryWithoutScopes()->insert($this->attributes->toDatabaseArray());

        if ($this->autoIncrements) {
            $this->attributes->set($this->primaryKey, $this->lastInsertId());
        }
    }

    protected function executeUpdate(): int
    {
        $this->attributes->updateTimestamps();
        $data = $this->attributes->toDatabaseArray();
        unset($data[$this->primaryKey]);

        $primaryKeyValue = $this->attributes->get($this->primaryKey);

        if ($primaryKeyValue === null) {
            throw new \RuntimeException('Primary key must be set to update the record.');
        }

        return self::query()->where($this->primaryKey, '=', $primaryKeyValue)->update($data);
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
