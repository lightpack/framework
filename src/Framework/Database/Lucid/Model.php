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

    public function __construct(Pdo $connection = null)
    {
        $this->data = new \stdClass();
        $this->connection = $connection ?? app('db');
    }

    public function __set($column, $value)
    {
        if (!method_exists($this, $column)) {
            $this->data->$column = $value;
        }
    }

    public function __get($column)
    {
        if (method_exists($this, $column)) {
            return $this->{$column}();
        }

        return $this->data->$column ?? null;
    }

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

    public function find(int $id, bool $fail = true): self
    {
        $this->data = $this->connection->table($this->table)->where($this->primaryKey, '=', $id)->fetchOne();

        if (!$this->data && $fail) {
            throw new RecordNotFoundException(
                sprintf('%s: No record found for ID = %d', get_called_class(), $id)
            );
        }

        return $this;
    }

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

    public function delete(): void
    {
        if (null === $this->{$this->primaryKey}) {
            return;
        }

        $this->beforeDelete();
        $this->connection->table($this->table)->where($this->primaryKey, '=', $this->{$this->primaryKey})->delete();
        $this->afterDelete();
    }

    public function query(): Query
    {
        return new Query($this->table, $this->connection);
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
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
