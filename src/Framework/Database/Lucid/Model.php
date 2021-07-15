<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\Pdo;
use Lightpack\Database\Query\Query;
use Lightpack\Exceptions\RecordNotFoundException;

class Model
{
    protected $key;
    protected $table;
    protected $data = [];
    protected $connection;

    public function __construct(string $table, Pdo $connection = null)
    {
        $this->key = 'id';
        $this->table = $table;
        $this->data = new \stdClass();
        $this->connection = $connection ?? app('db');
    }

    public function __set($column, $value)
    {
        if(!method_exists($this, $column)) {
            $this->data->$column = $value;
        }
    }

    public function __get($column)
    {
        if(method_exists($this, $column)) {
            return $this->{$column}();
        }

        return $this->data->$column ?? null;
    }

    public function setConnection(Pdo $connection)
    {
        $this->connection = $connection;
    }

    public function hasOne(string $model, string $foreignKey) 
    {
        $model = $this->connection->model($model);
        return $model->query()->where($foreignKey, '=', $this->{$this->key});
    }

    public function hasMany(string $model, string $foreignKey) 
    {
        $model = $this->connection->model($model);
        return $model->query()->where($foreignKey, '=', $this->{$this->key});
    }

    public function belongsTo(string $model, string $foreignKey)
    {
        $model = $this->connection->model($model);
        return $model->query()->where($this->key, '=', $this->{$foreignKey}); 
    }

    public function pivot(string $model, string $pivot, string $foreignKey, string $associateKey)
    {
        $model = $this->connection->model($model);
        return $model
                    ->query()
                    ->select(["$model->table.*"])
                    ->join($pivot, "$model->table.{$this->key}", "$pivot.$associateKey")
                    ->where("$pivot.$foreignKey", '=', $this->{$this->key});
    }

    public function find(int $id, bool $fail = true)
    {
        $this->data = $this->connection->table($this->table)->where($this->key, '=', $id)->fetchOne();

        if(!$this->data && $fail) {
            throw new RecordNotFoundException(
                sprintf('%s: No record found for ID = %d', get_called_class(), $id)
            );
        }

        return $this;
    }

    public function save()
    {
        if(null === $this->{$this->key}) {
            return $this->insert();
        }

        return $this->update();
    }

    public function delete()
    {
        if(null === $this->{$this->key}) {
            return false;
        }

        $this->connection->table($this->table)->where($this->key, '=', $this->{$this->key})->delete();
    }

    public function query()
    {
        return new Query($this->table, $this->connection);
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    private function insert()
    {
        $data = \get_object_vars($this->data);
        return $this->connection->table($this->table)->insert($data);
    }

    private function update()
    {
        $data = \get_object_vars($this->data);
        unset($data[$this->key]);
        return $this->connection->table($this->table)->where($this->key, '=', $this->{$this->key})->update($data);
    }
}