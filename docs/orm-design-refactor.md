# ORM Design Refactoring

This document outlines the proposed improvements to Lightpack's ORM architecture to achieve better separation of concerns, maintainability, and extensibility.

## Current Pain Points

1. **Model.php Issues**
   - Violates Single Responsibility Principle with too many responsibilities
   - Magic methods (__get/__set) make code behavior implicit
   - Relationship definitions mixed with model logic
   - Attribute handling tightly coupled with model

2. **Builder.php Concerns**
   - Inheritance from Query violates Liskov Substitution Principle
   - Complex relationship loading logic
   - Code duplication in relationship handling
   - Tight coupling between query building and relation loading

3. **Query.php Problems**
   - Large class with multiple responsibilities
   - Complex where clause construction
   - SQL compilation logic needs better separation
   - Lacks query caching mechanism

## Proposed Architecture

### 1. Model Layer Separation

```php
namespace Lightpack\Database\Lucid;

class BaseModel
{
    protected AttributeHandler $attributes;
    protected RelationshipManager $relations;
    
    public function __construct()
    {
        $this->attributes = new AttributeHandler($this);
        $this->relations = new RelationshipManager($this);
    }
}

class AttributeHandler
{
    protected array $attributes = [];
    protected array $casts = [];
    
    public function set(string $key, $value): void
    {
        // Handle attribute setting with type casting
    }
    
    public function get(string $key)
    {
        // Handle attribute retrieval with type casting
    }
}

class RelationshipManager
{
    protected array $relations = [];
    
    public function hasOne(string $related, string $foreignKey): HasOne
    {
        return new HasOne($this->model, $related, $foreignKey);
    }
    
    // Other relationship methods...
}
```

### 2. Query Builder Refactoring

```php
namespace Lightpack\Database\Query;

class QueryBuilder
{
    protected RelationLoader $relationLoader;
    protected QueryCompiler $compiler;
    
    public function __construct()
    {
        $this->relationLoader = new RelationLoader();
        $this->compiler = new QueryCompiler();
    }
}

class RelationLoader
{
    public function eagerLoad(Collection $models, array $relations)
    {
        // Handle eager loading of relationships
    }
}

class ModelBuilder extends QueryBuilder
{
    protected Model $model;
    
    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct();
    }
}
```

### 3. Query Components Separation

```php
namespace Lightpack\Database\Query;

class WhereClauseBuilder
{
    public function where($column, $operator = null, $value = null)
    {
        // Build where conditions
    }
    
    public function orWhere($column, $operator = null, $value = null)
    {
        // Build or where conditions
    }
}

class JoinClauseBuilder
{
    public function join($table, $first, $operator, $second)
    {
        // Build join clauses
    }
}

class QueryCompiler
{
    public function compile(Query $query): string
    {
        // Compile query to SQL
    }
}
```

## New Features to Add

### 1. Model Events System

```php
class ModelObserver
{
    public function creating(Model $model) {}
    public function created(Model $model) {}
    public function updating(Model $model) {}
    public function updated(Model $model) {}
    public function deleting(Model $model) {}
    public function deleted(Model $model) {}
}
```

### 2. Query Cache

```php
interface QueryCache
{
    public function get(string $key);
    public function put(string $key, $value, $ttl = null);
    public function forget(string $key);
}

class RedisQueryCache implements QueryCache
{
    // Implementation
}
```

### 3. Relationship Preloading

```php
class RelationshipPreloader
{
    public function preload(Collection $models, array $relations)
    {
        // Optimize relationship loading with single queries
    }
}
```

### 4. Query Profiler

```php
class QueryProfiler
{
    protected array $queries = [];
    
    public function log(string $query, float $time)
    {
        $this->queries[] = [
            'query' => $query,
            'time' => $time,
            'memory' => memory_get_usage(),
        ];
    }
}
```

## Migration Strategy

1. **Phase 1: Foundation**
   - Create new class structures
   - Implement basic functionality
   - Write comprehensive tests

2. **Phase 2: Integration**
   - Create adapters for backward compatibility
   - Gradually migrate existing models
   - Update documentation

3. **Phase 3: Enhancement**
   - Add new features (events, caching)
   - Performance optimization
   - Complete documentation

## Benefits

1. **Better Maintainability**
   - Clear separation of concerns
   - Smaller, focused classes
   - Explicit over implicit

2. **Enhanced Testability**
   - Isolated components
   - Clear dependencies
   - Better mock ability

3. **Improved Performance**
   - Query caching
   - Relationship optimization
   - Better memory usage

4. **Developer Experience**
   - Clear API
   - Better IDE support
   - Comprehensive documentation

## Backward Compatibility

The refactoring will maintain backward compatibility through:
- Adapter classes for old interfaces
- Deprecation notices for old methods
- Migration guides for users
- Comprehensive upgrade documentation
