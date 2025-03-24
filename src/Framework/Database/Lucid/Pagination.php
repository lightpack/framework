<?php

namespace Lightpack\Database\Lucid;

use Traversable;
use IteratorAggregate;
use Lightpack\Pagination\Pagination as BasePagination;

class Pagination extends BasePagination implements IteratorAggregate
{
    protected array $fields = [];
    protected array $includes = [];

    public function __construct(Collection $items, $total, $perPage = 10, $currentPage = null)
    {
        parent::__construct($items, $total, $perPage, $currentPage);
    }

    public function getIterator(): Traversable
    {
        return $this->items->getIterator();
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function load(string ...$relations): self
    {
        $this->items->load(...$relations);

        return $this;
    }

    public function loadCount(string ...$relations): self
    {
        $this->items->loadCount(...$relations);

        return $this;
    }

    public function toArray()
    {
        $arr = parent::toArray();

        $arr['items'] = $this->items->toArray();

        return $arr;
    }

    public function transform(array $fields = [], array $includes = []): self
    {
        $this->fields = $fields;
        $this->includes = $includes;
        $this->items = $this->items->transform($fields, $includes);
        return $this;
    }
}
