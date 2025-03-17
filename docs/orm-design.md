# ORM Design Decisions

This document outlines the key design decisions and architectural patterns used in our ORM implementation.

## Core Design Principles

1. **Separation of Concerns**: Split complex functionality into dedicated handler classes
2. **Single Responsibility**: Each class has a clear, focused purpose
3. **Extensibility**: Easy to add new features without modifying existing code
4. **Maintainability**: Changes to specific functionality only need to be made in one place

## Key Components

### Model Class

The base Model class serves as the primary interface for database operations while delegating complex functionality to specialized handlers.

#### Design Decisions:

1. **Query Method vs Direct Query**
   - `find()` uses `new Query()` instead of `self::query()`
   - Reason: Avoid Builder's hydration which would create new model instances
   - Direct Query allows find() to control how data is loaded into the current model

2. **Attribute Management**
   - Delegated to `AttributeHandler` class
   - Handles:
     - Basic get/set operations
     - Hidden fields
     - Timestamps
     - Array/JSON conversion
   - Benefits:
     - Encapsulated attribute logic
     - Easy to extend with new features (validation, type casting, dirty tracking)
     - Consistent attribute handling across models

3. **Relationship Management**
   - Delegated to `RelationHandler` class
   - Manages:
     - Multiple relationship types (hasOne, hasMany, belongsTo, pivot, hasManyThrough)
     - Relationship metadata
     - Query building for each type
     - Eager/lazy loading states
     - Result caching
   - Benefits:
     - Complex relationship logic isolated
     - Easy to add new relationship types
     - Consistent relationship behavior
     - Simplified testing

4. **Query Building**
   - Builder class extends Query
   - Adds model-aware functionality:
     - Result hydration
     - Relationship eager loading
     - Model hooks (beforeFetch, afterFetch)
   - Used for complex queries beyond simple ID lookups

## Architectural Patterns

### Handler Pattern
We use dedicated handler classes to manage complex aspects of the ORM:

1. **Why Handlers?**
   - Break down complex functionality
   - Clear separation of concerns
   - Easier to maintain and test
   - More flexible and extensible

2. **Benefits**
   - Focused testing
   - Isolated bug fixes
   - Easy to add features
   - Clean Model class

### Builder Pattern
The Query Builder pattern is implemented through:

1. **Base Query Class**
   - Core query building functionality
   - SQL compilation
   - Basic result fetching

2. **Model-Aware Builder**
   - Extends Query
   - Adds model-specific features
   - Handles result hydration

## Future Extensibility

The current architecture allows for easy addition of:

1. **Attribute Features**
   - Type casting
   - Validation
   - Computed properties
   - Dirty tracking

2. **Relationship Types**
   - Polymorphic relations
   - More through relations
   - Custom relation types

3. **Query Features**
   - Scopes
   - Global scopes
   - Advanced eager loading

## Testing Considerations

The architecture supports effective testing through:

1. **Isolated Components**
   - Each handler can be tested independently
   - Model tests can use mock handlers
   - Relationship tests don't need full models

2. **Clear Boundaries**
   - Each class has clear responsibilities
   - Easy to write focused unit tests
   - Clear what to test in each component
