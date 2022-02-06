<?php

namespace Lightpack\Database\Lucid;

use Closure;
use Exception;
use JsonSerializable;
use Lightpack\Database\Pdo;
use Lightpack\Database\Query\Query;
use Lightpack\Exceptions\RecordNotFoundException;

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
     * @var stdClass Model data object 
     */
    protected $data;

    /** 
     * @var \Lightpack\Database\Pdo
     */
    protected $connection;

    /**
     * @var bool Timestamps
     */
    protected $timestamps = false;

    /**
     * @var string Type of relation to be resolved.
     */
    protected $relationType;

     /**
     * @var string Model associated with the relation.
     */
    protected $relatingModel;

    /**
     * @var string The key used to resolve the relation while eager loading.
     */
    protected $relatingKey;

    /**
     * @var string The foreign key used to resolve the relation while eager loading.
     */
    protected $relatingForeignKey;

    /**
     * @var string Pivot table when resolving many-to-many relationship.
     */
    protected $pivotTable;

    /**
     * @var array Cached models that have already been loaded.
     */
    protected $cachedModels = [];

    /**
     * @var \Lightpack\Database\Lucid\Builder
     */
    protected $builder;

    /**
     * @var array Attributes to be hidden for serialization or array conversion.
     */
    protected $hidden = [];

    /**
     * Constructor.
     *
     * @param [int|string] $id
     * @param Pdo $connection
     */
    public function __construct($id = null)
    {
        $this->data = new \stdClass();

        if ($id) {
            $this->find($id);
        }
    }

    /**
     * Sets the model properties.
     *
     * @param string $column
     * @param mix $value
     */
    public function __set($column, $value)
    {
        if (!method_exists($this, $column)) {
            $this->data->$column = $value;
        }
    }

    /**
     * Returns a model property or executes a relation
     * method if present.
     *
     * @param string $key
     * @return void
     */
    public function __get(string $key)
    {
        if (property_exists($this->data, $key)) {
            return $this->data->$key;
        }

        if (!method_exists($this, $key)) {
            return $this->data->$key ?? null;
        }

        if (array_key_exists($key, $this->cachedModels)) {
            return $this->cachedModels[$key];
        }

        $query = $this->{$key}();

        if ($this->relationType === 'hasMany' || $this->relationType === 'pivot') {
            return $this->cachedModels[$key] = $query->all();
        }

        return $this->cachedModels[$key] = $query->one();
    }

    /**
     * Sets the database connection to be used for querying
     * the tables.
     *
     * @param Pdo $connection
     * @return void
     */
    public function setConnection(Pdo $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Sets the database connection to be used for querying
     * the tables.
     *
     * @return Pdo $connection
     */
    public function getConnection(): Pdo
    {
        return $this->connection ?? app('db');
    }

    /**
     * This method maps 1:1 relationship with the provided model.
     *
     * @param string $model The relating model class name.
     * @param string $foreignKey
     * @return Query
     */
    public function hasOne(string $model, string $foreignKey): Query
    {
        $this->relationType = __FUNCTION__;
        $this->relatingKey = $foreignKey;
        $this->relatingForeignKey = $foreignKey;
        // $this->relatingForeignKey = $this->primaryKey;
        $this->relatingModel = $model;
        $model = $this->getConnection()->model($model);
        return $model::query()->where($foreignKey, '=', $this->{$this->primaryKey});
    }

    /**
     * This method maps 1:N relationship with the provided model.
     *
     * @param string $model The relating model class name.
     * @param string $foreignKey
     * @return Query
     */
    public function hasMany(string $model, string $foreignKey): Query
    {
        $this->relationType = __FUNCTION__;
        $this->relatingKey = $foreignKey;
        $this->relatingForeignKey = $this->primaryKey;
        $this->relatingModel = $model;
        return $model::query()->where($foreignKey, '=', $this->{$this->primaryKey});
    }

    /**
     * This method maps belongs to relationship with the provided model.
     *
     * @param string $model The relating model class name.
     * @param string $foreignKey
     * @return Query
     */
    public function belongsTo(string $model, string $foreignKey): Query
    {
        $model = $this->getConnection()->model($model);
        $this->relationType = __FUNCTION__;
        $this->relatingKey = $model->getPrimaryKey();
        $this->relatingForeignKey = $foreignKey;
        $this->relatingModel = $model;
        return $model::query()->where($this->primaryKey, '=', $this->{$foreignKey});
    }

    /**
     * This method maps N:N relationship with the provided model.
     *
     * @param string $model The relating model class name.
     * @param string $pivot Name of the pivot table.
     * @param string $foreignKey
     * @param string $associateKey
     * @return Query
     */
    public function pivot(string $model, string $pivotTable, string $foreignKey, string $associateKey): Query
    {
        $this->relationType = __FUNCTION__;
        $this->relatingKey = $foreignKey;
        $this->relatingForeignKey = $foreignKey;
        $this->relatingModel = $model;
        $this->pivotTable = $pivotTable;
        $model = $this->getConnection()->model($model);
        return $model
            ->query()
            ->select("$model->table.*")
            ->join($pivotTable, "$model->table.{$this->primaryKey}", "$pivotTable.$associateKey")
            ->where("$pivotTable.$foreignKey", '=', $this->{$this->primaryKey});
    }

    /**
     * Find a record by its primary key.
     *
     * @param [int|string] $id
     * @param boolean $fail
     * @return self
     */
    public function find($id, bool $fail = true): self
    {
        $this->data = $this->query()->where($this->primaryKey, '=', $id)->fetchOne();

        if (!$this->data && $fail) {
            throw new RecordNotFoundException(
                sprintf('%s: No record found for ID = %d', get_called_class(), $id)
            );
        }

        return $this;
    }

    /**
     * Insert or update a model.
     *
     * @return void
     */
    public function save(): void
    {
        $this->setTimestamps();
        $this->beforeSave();

        if ($this->{$this->primaryKey}) {
            $this->update();
        } else {
            $this->insert();
        }

        $this->afterSave();
    }

    /**
     * Deletes a model.
     */
    public function delete($id = null)
    {
        if ($id) {
            $this->find($id);
        }

        if (!isset($this->data->{$this->primaryKey})) {
            return;
        }

        $this->beforeDelete();

        $this->query()->where($this->primaryKey, '=', $this->{$this->primaryKey})->delete();

        $this->afterDelete();
    }

    /**
     * Returns the last inserted row id.
     *
     * @return void
     */
    public function lastInsertId()
    {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * Returns the database table name the model
     * represents.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->table;
    }

    /**
     * Returns the primary key identifier.
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public static function query(): Builder
    {
        return new Builder(new static);
    }

    /**
     * Acts as a hook method to be called before executing
     * save() method on model.
     *
     * @return void
     */
    protected function beforeSave()
    {
        // 
    }

    /**
     * Acts as a hook method to be called after executing
     * save() method on model.
     *
     * @return void
     */
    protected function afterSave()
    {
        // 
    }

    /**
     * Acts as a hook method to be called before executing
     * delete() method on model.
     *
     * @return void
     */

    protected function beforeDelete()
    {
        // 
    }

    /**
     * Acts as a hook method to be called after executing
     * delete() method on model.
     *
     * @return void
     */
    protected function afterDelete()
    {
        // 
    }

    protected function insert()
    {
        $data = \get_object_vars($this->data);
        return $this->query()->insert($data);
    }

    protected function update()
    {
        $data = \get_object_vars($this->data);
        unset($data[$this->primaryKey]);
        return $this->query()->where($this->primaryKey, '=', $this->{$this->primaryKey})->update($data);
    }

    protected function setTimestamps()
    {
        if (false === $this->timestamps) {
            return;
        }

        
        if ($this->data->{$this->primaryKey} ?? false) {
            $this->data->updated_at = date('Y-m-d H:i:s');
        } else {
            $this->data->created_at = date('Y-m-d H:i:s');
        }
    }

    public function jsonSerialize()
    {
        array_map(function ($key) {
            unset($this->data->{$key});
        }, $this->hidden);

        return $this->data;
    }

    public function getRelationType()
    {
        return $this->relationType;
    }

    public function getRelatingKey()
    {
        return $this->relatingKey;
    }

    public function getRelatingForeignKey()
    {
        return $this->relatingForeignKey;
    }

    public function getRelatingModel()
    {
        return $this->relatingModel;
    }

    public function getPivotTable()
    {
        return $this->pivotTable;
    }

    public function getAttributes()
    {
        return $this->data;
    }

    public function getAttribute($key, $default = null)
    {
        return $this->data->{$key} ?? $default;
    }

    public function setAttributes(array $data)
    {
        $this->data = (object) $data;
    }

    public function hasAttribute(string $key)
    {
        return property_exists($this->data, $key);
    }

    public function setAttribute($key, $value)
    {
        $this->data->{$key} = $value;
    }
}
