<?php

namespace Lightpack\Database\Lucid;

use Closure;
use Countable;
use Traversable;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;

class Collection implements IteratorAggregate, Countable, JsonSerializable
{
    protected $items = [];

    /**
     * Populate ietems keyed by model id
     *
     * @param \Lightpack\Database\Lucid\Model|\Lightpack\Database\Lucid\Model[] $items
     */
    public function __construct($items)
    {
        if ($items instanceof Model) {
            $items = [$items];
        }
        
        foreach ($items as $item) {
            $this->items[$item->{$item->getPrimaryKey()}] = $item;
        }
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getKeys(): array
    {
        return array_keys($this->items);
    }

    public function getByKey($key)
    {
        return $this->items[$key] ?? null;
    }

    public function getItemWherecolumn($column, $value)
    {
        foreach ($this->items as $item) {
            if ($item->{$column} == $value) {
                return $item;
            }
        }

        return null;
    }

    public function getByColumn(string $column)
    {
        $data = [];

        foreach ($this->items as $item) {
            if ($item->$column ?? false) {
                $data[] = $item->$column;
            }
        }

        return $data;
    }

    public function columnExists(string $column)
    {
        foreach ($this->items as $item) {
            if ($item->hasAttribute($column)) {
                return true;
            }
        }

        return false;
    }

    public function filter(Closure $callback): Collection
    {
        return new static(array_filter($this->items, $callback));
    }

    public function jsonSerialize()
    {
        return array_values($this->items);
    }

    public function load(string ...$relations): self
    {
        if (empty($relations) || empty($this->items)) {
            return $this;
        }

        $model = get_class(reset($this->items));
        $items = new Collection($this->items);
        $model::query()->with(...$relations)->eagerLoadRelations($items);

        return $this;
    }

    public function loadCount(string ...$relations): self
    {
        if (empty($relations) || empty($this->items)) {
            return $this;
        }

        $model = get_class(reset($this->items));
        $items = new Collection($this->items);
        $model::query()->withCount(...$relations)->eagerLoadRelationsCount($items);

        return $this;
    }

    public function mapAndCreate(string $field, array $data, string $key = null, array $pluckKeys = [], $default = null): self
    {
        array_map(function ($item) use ($field, $data, $key, $pluckKeys) {
            array_map(function ($value) use ($item, $field, $key, $pluckKeys) {
                if ($value->$key === $item->id) {
                    if (!$pluckKeys) {
                        $item->setAttribute($field, $value);
                    } else {
                        $setData = [];

                        foreach ($pluckKeys as $pluckKey) {
                            $setData[$pluckKey] = $value->$pluckKey;
                        }

                        $item->setAttribute($field, $setData);
                    }
                }
            }, $data);

            // set the field to empty object in case not found
            if ($item->getAttribute($field) === null) {
                $item->setAttribute($field, $default ?? new \stdClass);
            }
        }, $this->items);

        return $this;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function last()
    {
        return end($this->items);
    }

    public function first()
    {
        return reset($this->items);
    }

    public function isEmpty()
    {
        return empty($this->items);
    }

    public function toArray()
    {
        return array_map(function ($item) {
            return $item->toArray();
        }, $this->items);
    }
}
