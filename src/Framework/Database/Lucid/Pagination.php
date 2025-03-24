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

        $arr['items'] = is_array($this->items) ? $this->items : $this->items->toArray();

        return $arr;
    }

    public function transform(array $fields = [], array $includes = []): array
    {
        $totalPages = (int) ceil($this->total / $this->perPage);
        
        $result = [
            'data' => $this->items->transform($fields, $includes),
            'meta' => [
                'current_page' => $this->currentPage,
                'per_page' => $this->perPage,
                'total' => $this->total,
                'total_pages' => $totalPages
            ],
            'links' => [
                'first' => '?page=1',
                'last' => '?page=' . $totalPages,
                'prev' => $this->currentPage > 1 ? '?page=' . ($this->currentPage - 1) : null,
                'next' => $this->currentPage < $totalPages ? '?page=' . ($this->currentPage + 1) : null
            ]
        ];

        return $result;
    }
}
