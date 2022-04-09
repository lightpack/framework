<?php

namespace Lightpack\Pagination;

class Pagination
{
    protected $total;
    protected $perPage;
    protected $currentPage;
    protected $lastPage;
    protected $path;
    protected $allowedParams = [];
    
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

    public function currentPage()
    {
        return $this->currentPage;
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

    public function url(int $page)
    {
        $query = $this->getQuery($page);
        return $this->path . '?' . $query;
    }

    public function only(array $params = [])
    {
        $this->allowedParams = $params;

        return $this;
    }

    public function items()
    {
        return $this->items;
    }

    public function hasNextPage()
    {
        return $this->currentPage < $this->lastPage;
    }

    public function hasPreviousPage()
    {
        return $this->currentPage > 1;
    }

    public function hasLinks()
    {
        return $this->lastPage > 1;
    }

    public function getPages()
    {
        $pages = [];
        $start = 1;
        $end = $this->lastPage;

        if($this->lastPage > 5) {
            $start = $this->currentPage - 2;
            $end = $this->currentPage + 2;

            if($start < 1) {
                $start = 1;
                $end = 5;
            }

            if($end > $this->lastPage) {
                $end = $this->lastPage;
                $start = $this->lastPage - 4;
            }
        }

        for($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        return $pages;
    }

    protected function getQuery(int $page): string
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

    protected function setCurrentPage($currentPage = null)
    {
        $this->currentPage = $currentPage ?? app('request')->get('page', 1);
        $this->currentPage = (int) $this->currentPage;
        $this->currentPage = $this->currentPage > 0 ? $this->currentPage : 1;
    }
}