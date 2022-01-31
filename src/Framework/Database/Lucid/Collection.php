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

    public function __construct($items)
    {
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
        return array_keys($this->items);
    }

    public function getByKey($key)
    {
        return $this->items[$key];
    }

    public function getByColumn(string $column)
    {
        $data = [];

        foreach($this->items as $item) {
            if($item->$column ?? false) {
                $data[] = $item->$column;
            }
        }

        return $data;
    }

    public function filter(Closure $callback): Collection
	{
		return new static(array_filter($this->items, $callback));
	}

    public function jsonSerialize()
    {
        return $this->items;
    }

    public function load(string ...$relations): self
    {
        if(empty($relations) || empty($this->items)) {
            return $this;
        }

        $model = get_class(reset($this->items));

        $items = new Collection($this->items);

        (new $model)->with(...$relations)->eagerLoadRelations($items);

        return $this;
    }

    public function loadCount(string ...$relations): self
    {
        if(empty($relations) || empty($this->items)) {
            return $this;
        }

        $model = get_class(reset($this->items));

        $items = new Collection($this->items);

        (new $model)->withCount(...$relations)->eagerLoadRelationsCount($items);

        return $this;
    }
}