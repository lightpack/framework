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
                    "Strict Mode: Relation '%s' must be eager loaded. Use %s::with('%s')->get()",
                    $key,
                    get_class($this),
                    $key
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

    public function hasOne(string $model, string $foreignKey): Query
    {
        return $this->relations->hasOne($model, $foreignKey);
    }

    public function hasMany(string $model, string $foreignKey): Query
    {
        return $this->relations->hasMany($model, $foreignKey);
    }

    public function belongsTo(string $model, string $foreignKey): Query
    {
        return $this->relations->belongsTo($model, $foreignKey);
    }

    public function pivot(string $model, string $pivotTable, string $foreignKey, string $associateKey): Pivot
    {
        return $this->relations->pivot($model, $pivotTable, $foreignKey, $associateKey);
    }

    public function hasManyThrough(string $model, string $through, string $throughKey, string $foreignKey): Query
    {
        return $this->relations->hasManyThrough($model, $through, $throughKey, $foreignKey);
    }

    public function find($id, bool $fail = true): self
    {
        $query = new Query($this->table, $this->getConnection());
        $this->beforeFind($query);
        $data = $query->where($this->primaryKey, '=', $id)->one();

        if (!$data && $fail) {
            throw new RecordNotFoundException(
                sprintf('%s: No record found for ID = %d', get_called_class(), $id)
            );
        }

        if ($data) {
            $this->attributes->fillRaw((array)$data);
        }
        
        $this->afterFind();
        return $this;
    }

    public function save(): void
    {
        $primaryKeyValue = $this->attributes->get($this->primaryKey);
        $this->attributes->updateTimestamps($primaryKeyValue !== null);
        $query = $this->query();
        
        $this->beforeSave($query);

        if ($primaryKeyValue !== null) {
            $this->update($query);
        } else {
            $this->insert($query);
            $this->attributes->set($this->primaryKey, $this->lastInsertId());
        }

        $this->afterSave();
    }

    public function delete() {
        if (!$this->attributes->get($this->primaryKey)) {
            throw new \RuntimeException('Cannot delete: model has no ID');
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
        return new Builder(new static);
    }

    protected function beforeFind(Query $query)
    {
        // Hook method
    }

    protected function afterFind()
    {
        // Hook method
    }

    protected function beforeSave(Query $query)
    {
        // Hook method
    }

    protected function afterSave()
    {
        // Hook method
    }

    protected function beforeDelete(Query $query)
    {
        // Hook method
    }

    protected function afterDelete()
    {
        // Hook method
    }

    protected function insert(Query $query)
    {
        return $query->insert($this->attributes->toDatabaseArray());
    }

    protected function update(Query $query)
    {
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
                if($value instanceof Collection || $value instanceof Model) {
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

        foreach($data as $key => $value) {
            if(!in_array($key, $exclude)) {
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
}
