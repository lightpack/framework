<?php

namespace Lightpack\Database\Lucid;

use Traversable;

class Collection implements \IteratorAggregate, \Countable, \JsonSerializable
{
    protected $items = [];

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
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

        (new $model)->with(...$relations)->eagerLoad($this->items);

        return $this;
    }
}