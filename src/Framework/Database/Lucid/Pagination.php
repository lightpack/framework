<?php

namespace Lightpack\Database\Lucid;

use Countable;
use Traversable;
use JsonSerializable;
use IteratorAggregate;
use Lightpack\Pagination\Pagination as BasePagination;

class Pagination extends BasePagination implements Countable, IteratorAggregate, JsonSerializable
{
    public function __construct($total, $perPage = 10, $currentPage = null, Collection $items)
    {
        parent::__construct($total, $perPage, $currentPage, $items);
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

    public function jsonSerialize()
    {
        return [
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'path' => $this->path,
            'links' => [
                'next' => $this->nextPageUrl(),
                'prev' => $this->prevPageUrl(),
            ],
            'items' => $this->items,
        ];
    }
}