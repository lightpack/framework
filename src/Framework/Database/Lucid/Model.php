<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\Pdo;
use Lightpack\Database\Query\Query;
use Lightpack\Exceptions\RecordNotFoundException;

class Model
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
     * Constructor.
     *
     * @param [int|string] $id
     * @param Pdo $connection
     */
    public function __construct($id = null, Pdo $connection = null)
    {
        $this->data = new \stdClass();
        $this->connection = $connection ?? app('db');

        if($id) {
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
     * @param string $column
     * @return void
     */
    public function __get(string $column)
    {
        if (method_exists($this, $column)) {
            return $this->{$column}();
        }

        return $this->data->$column ?? null;
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
     * This method maps 1:1 relationship with the provided model.
     *
     * @param string $model The relating model class name.
     * @param string $foreignKey
     * @return Query
     */
    public function hasOne(string $model, string $foreignKey): Query
    {
        $model = $this->connection->model($model);
        return $model->query()->where($foreignKey, '=', $this->{$this->primaryKey});
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
        $model = $this->connection->model($model);
        return $model->query()->where($foreignKey, '=', $this->{$this->primaryKey});
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
        $model = $this->connection->model($model);
        return $model->query()->where($this->primaryKey, '=', $this->{$foreignKey});
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
        $model = $this->connection->model($model);
        return $model
            ->query()
            ->select(["$model->table.*"])
            ->join($pivotTable, "$model->table.{$this->primaryKey}", "$pivotTable.$associateKey")
            ->where("$pivotTable.$foreignKey", '=', $this->{$this->primaryKey});
    }

    /**
     * Enables eager loading relationships.
     * 
     * @todo Needs refactoring it using collections by
     * hydrating arrays into objects.
     *
     * @param string ...$models
     * @return object|array
     */
    private function with(string ...$models)
    {
        $data = $this->query->all(true);
        $data = array_column($data, null, $this->primaryKey);
        $ids = array_column($data, 'department_id');

        foreach($models as $model) {
            $modelTable = (new $model)->getTableName();
            $dataChild = (new $model)->query()->whereIn($this->primaryKey, $ids)->all(true);

            foreach($dataChild as $r) {
                $data[$r[$this->primaryKey]][$modelTable][] = $r;
            }
        }

        return $data;
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
        $this->data = $this->connection->table($this->table)->where($this->primaryKey, '=', $id)->fetchOne();

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
     *
     * @return void
     */
    public function delete(): void
    {
        if (null === $this->{$this->primaryKey}) {
            return;
        }

        $this->beforeDelete();
        $this->connection->table($this->table)->where($this->primaryKey, '=', $this->{$this->primaryKey})->delete();
        $this->afterDelete();
    }

    /**
     * Returns the last inserted row id.
     *
     * @return void
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
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

    /**
     * Makes models capable of querying data. 
     *
     * @return Query
     */
    public function query(): Query
    {
        return new Query($this->table, $this->connection);
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

    private function insert()
    {
        $data = \get_object_vars($this->data);
        return $this->connection->table($this->table)->insert($data);
    }

    private function update()
    {
        $data = \get_object_vars($this->data);
        unset($data[$this->primaryKey]);
        return $this->connection->table($this->table)->where($this->primaryKey, '=', $this->{$this->primaryKey})->update($data);
    }

    private function setTimestamps()
    {
        if (false === $this->timestamps) {
            return;
        }

        $this->data->updated_at = date('Y-m-d H:i:s');

        if ($this->data->{$this->primaryKey}) {
            $this->data->created_at = date('Y-m-d H:i:s');
        }
    }
}
