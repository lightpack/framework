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
     * @var object Pdo connection instance
     */
    protected $connection;

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

    public function setConnection(Pdo $connection)
    {
        $this->connection = $connection;
    }

    public function hasOne(string $model, string $foreignKey)
    {
        $model = $this->connection->model($model);
        return $model->query()->where($foreignKey, '=', $this->{$this->primaryKey});
    }

    public function hasMany(string $model, string $foreignKey)
    {
        $model = $this->connection->model($model);
        return $model->query()->where($foreignKey, '=', $this->{$this->primaryKey});
    }

    public function belongsTo(string $model, string $foreignKey)
    {
        $model = $this->connection->model($model);
        return $model->query()->where($this->primaryKey, '=', $this->{$foreignKey});
    }

    public function pivot(string $model, string $pivot, string $foreignKey, string $associateKey)
    {
        $model = $this->connection->model($model);
        return $model
            ->query()
            ->select(["$model->table.*"])
            ->join($pivot, "$model->table.{$this->primaryKey}", "$pivot.$associateKey")
            ->where("$pivot.$foreignKey", '=', $this->{$this->primaryKey});
    }

    public function find(int $id, bool $fail = true)
    {
        $this->data = $this->connection->table($this->table)->where($this->primaryKey, '=', $id)->fetchOne();

        if (!$this->data && $fail) {
            throw new RecordNotFoundException(
                sprintf('%s: No record found for ID = %d', get_called_class(), $id)
            );
        }

        return $this;
    }

    public function save()
    {
        if (null === $this->{$this->primaryKey}) {
            return $this->insert();
        }

        return $this->update();
    }

    public function delete()
    {
        if (null === $this->{$this->primaryKey}) {
            return false;
        }

        $this->connection->table($this->table)->where($this->primaryKey, '=', $this->{$this->primaryKey})->delete();
    }

    public function query()
    {
        return new Query($this->table, $this->connection);
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    public function getTableName()
    {
        return $this->table;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
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
}
