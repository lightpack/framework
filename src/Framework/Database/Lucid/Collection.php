<?php

namespace Lightpack\Database\Lucid;

use ArrayAccess;
use Closure;
use Countable;
use Traversable;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;

class Collection implements IteratorAggregate, Countable, JsonSerializable, ArrayAccess
{
    protected array $items = [];

    /**
     * @param \Lightpack\Database\Lucid\Model|\Lightpack\Database\Lucid\Model[] $items
     */
    public function __construct($items)
    {
        if ($items instanceof Model) {
            $items = [$items];
        }

        $this->items = $items;
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
        foreach($this->items as $item) {
            $keys[] = $item->{$item->getPrimaryKey()};
        }

        return $keys ?? [];
    }

    public function getByKey($key, $default = null)
    {
        foreach ($this->items as $item) {
            if ($item->{$item->getPrimaryKey()} == $key) {
                return $item;
            }
        }

        return $default;
    }

    public function getItemWhereColumn($column, $value): ?Model
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
        $items = array_filter($this->items, $callback);
        $items = array_values($items);

        return new static($items);
    }

    public function jsonSerialize(): mixed
    {
        return array_values($this->items);
    }

    public function load(): self
    {
        $relations = func_get_args();

        if (empty($relations) || empty($this->items)) {
            return $this;
        }

        $model = get_class(reset($this->items));
        $items = new Collection($this->items);
        $model::query()->with(...$relations)->eagerLoadRelations($items);

        return $this;
    }

    public function loadCount(): self
    {
        $relations = func_get_args();

        if (empty($relations) || empty($this->items)) {
            return $this;
        }

        $model = get_class(reset($this->items));
        $items = new Collection($this->items);
        $model::query()->withCount(...$relations)->eagerLoadRelationsCount($items);

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return $this->isEmpty() === false;
    }

    public function exclude(array|string|int $keys): self
    {
        if(!is_array($keys)) {
            $keys = [$keys];
        }

        $items = array_filter($this->items, function($item) use ($keys) {
            return !in_array($item->{$item->getPrimaryKey()}, $keys);
        });

        return new static(array_values($items));
    }

    public function toArray(): array
    {
        return array_map(function ($item) {
            return $item->toArray();
        }, $this->items);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (!$value instanceof Model) {
            throw new \InvalidArgumentException('Collection items must be instances of Model');
        }

        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function each(Closure $callback): self
    {
        foreach ($this->items as $item) {
            $callback($item);
        }

        return $this;
    }
}
