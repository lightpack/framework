<?php

namespace Lightpack\Routing;

use Lightpack\Database\Lucid\Model;
use Lightpack\Exceptions\RecordNotFoundException;

/**
 * Resolves route model bindings.
 * 
 * This class is responsible for automatically loading model instances
 * based on route parameters when explicitly declared via Route::bind().
 */
class ModelBinder
{
    /**
     * Per-request cache to prevent duplicate queries.
     * 
     * @var array
     */
    private array $cache = [];
    
    /**
     * Resolve all model bindings for a route.
     * 
     * @param array $bindings Route binding configuration from Route::getBindings()
     * @param array $params Route parameters extracted from URL
     * @return array Resolved model instances keyed by parameter name
     * @throws RecordNotFoundException If model not found and parameter is required
     */
    public function resolve(array $bindings, array $params): array
    {
        $resolved = [];
        
        foreach ($bindings as $param => $config) {
            $value = $params[$param] ?? null;
            
            // Skip if parameter not present (optional parameter)
            if ($value === null) {
                continue;
            }
            
            // Resolve the model
            $model = $this->findModel(
                $config['model'],
                $value,
                $config['field']
            );
            
            // Store by parameter name for injection
            $resolved[$param] = $model;
        }
        
        return $resolved;
    }
    
    /**
     * Find a model instance by value.
     * 
     * @param string $modelClass Fully qualified model class name
     * @param mixed $value The value to search for
     * @param string|null $field The database column to query (null = primary key)
     * @return Model The resolved model instance
     * @throws RecordNotFoundException If model not found
     */
    private function findModel(string $modelClass, mixed $value, ?string $field): Model
    {
        // Generate cache key
        $cacheKey = $modelClass . ':' . ($field ?? 'pk') . ':' . $value;
        
        // Return cached instance if available
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        /** @var Model $model */
        $model = new $modelClass();
        
        // Determine which field to query
        // If no field specified, use model's primary key
        $queryField = $field ?? $model->getPrimaryKey();
        
        // Execute query
        $result = $modelClass::query()
            ->where($queryField, '=', $value)
            ->one();
        
        // Throw exception if not found
        if (!$result) {
            throw new RecordNotFoundException(
                sprintf(
                    'No %s found with %s = %s',
                    $modelClass,
                    $queryField,
                    $value
                )
            );
        }
        
        // Cache and return
        $this->cache[$cacheKey] = $result;
        return $result;
    }
    
    /**
     * Clear the resolution cache.
     * Useful for testing.
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
