<?php

namespace Lightpack\Pagination;

class Pagination implements \IteratorAggregate, \Countable, \JsonSerializable
{
    private $total;
    private $perPage;
    private $currentPage;
    private $lastPage;
    private $path;
    private $allowedParams = [];
    
    public function __construct($total, $perPage = 10, $currentPage = null, $items = [])
    {
        $this->total = $total;
        $this->perPage = $perPage;
        $this->lastPage = ceil($this->total / $this->perPage);
        $this->path = app('request')->fullpath();
        $this->setCurrentPage($currentPage);
        $this->items = $items;
    }

    public function links()
    {
        if($this->lastPage <= 1) {
            return '';
        }
        
        $prevLink = $this->prev();
        $nextLink = $this->next();
        $template = "Page {$this->currentPage} of {$this->lastPage} {$prevLink}  {$nextLink}";

        return $template;
    }

    public function withPath($path) {   
        $this->path = url($path);
        return $this;
    }

    public function total()
    {
        return $this->total;
    }

    public function limit()
    {
        return $this->perPage;
    }
    
    public function offset()
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function lastPage()
    {
        return $this->lastPage;
    }

    public function next()
    {
        $next = $this->currentPage < $this->lastPage ? $this->currentPage + 1 : null;
        
        if($next) {
            $query = $this->getQuery($next);
            return "<a href=\"{$this->path}?{$query}\">Next</a>";
        }
    }

    public function prev()
    {
        $prev = $this->currentPage > 1 ? $this->currentPage - 1 : null;
        
        if($prev) {
            $query = $this->getQuery($prev);
            return "<a href=\"{$this->path}?{$query}\">Prev</a>";
        }
    }

    public function nextPageUrl()
    {
        $next = $this->currentPage < $this->lastPage ? $this->currentPage + 1 : null;

        if($next) {
            $query = $this->getQuery($next);
            return $this->path . '?' . $query;
        }
    }

    public function prevPageUrl()
    {
        $prev = $this->currentPage > 1 ? $this->currentPage - 1 : null;
        
        if($prev) {
            $query = $this->getQuery($prev);
            return $this->path . '?' . $query;
        }
    }

    public function only(array $params = [])
    {
        $this->allowedParams = $params;

        return $this;
    }

    private function getQuery(int $page): string
    {
        $params = $_GET; 
        $allowedParams = $this->allowedParams;

        if ($allowedParams) {
            $params = \array_filter($_GET, function ($key) use ($allowedParams) {
                return \in_array($key, $allowedParams);
            });
        }

        $params = array_merge($params, ['page' => $page]);

        return http_build_query($params);
    }

    private function setCurrentPage($currentPage = null)
    {
        $this->currentPage = $currentPage ?? app('request')->get('page', 1);
        $this->currentPage = (int) $this->currentPage;
        $this->currentPage = $this->currentPage > 0 ? $this->currentPage : 1;
    }

    public function items()
    {
        return $this->items;
    }

    public function getIterator(): \Traversable
    {
        if($this->items instanceof \Traversable) {
            return $this->items->getIterator();
        }

        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
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