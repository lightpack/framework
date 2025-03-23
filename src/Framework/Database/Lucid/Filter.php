<?php

namespace App\QueryFilters;

use Lightpack\Database\Query\Query;
use Lightpack\Http\Request;

abstract class Filter
{
    protected ?Query $query = null;
    protected array $filters = [];
    protected Request $request;
    protected bool $autoDetect = true;
    
    public function __construct(?Query $query = null)
    {
        $this->query = $query;
        $this->request = app('request');
        
        if ($this->autoDetect) {
            $this->extractFiltersFromRequest();
        }
    }
    
    public function apply(): Query
    {
        if (!$this->query) {
            throw new \RuntimeException('No query instance provided to filter');
        }
        
        foreach ($this->filters as $name => $value) {
            if (method_exists($this, $name)) {
                $this->{$name}($this->parseValue($value));
            }
        }
        
        return $this->query;
    }
    
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }
    
    public function withAutoDetect(bool $enable = true): self 
    {
        $this->autoDetect = $enable;
        if ($enable) {
            $this->extractFiltersFromRequest();
        }
        return $this;
    }
    
    public function mergeFilters(array $filters): self
    {
        $this->filters = array_merge($this->filters, $filters);
        return $this;
    }
    
    protected function extractFiltersFromRequest(): void
    {
        $filters = $this->request->query('filter', []);
        
        if (is_array($filters)) {
            foreach ($filters as $name => $value) {
                if (method_exists($this, $name)) {
                    $this->filters[$name] = $value;
                }
            }
        }
    }
    
    protected function parseValue($value)
    {
        // Handle comma-separated values
        if (is_string($value) && str_contains($value, ',')) {
            return explode(',', $value);
        }
        
        return $value;
    }
}
