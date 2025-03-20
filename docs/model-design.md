# Model Design

## Core Components

### 1. Handlers
The Model class delegates specific responsibilities to dedicated handlers:

#### AttributeHandler
- Manages model attributes and their lifecycle
- Handles timestamps and serialization
- Provides type casting for attributes

```php
class User extends Model {
    protected $casts = [
        'settings' => 'json',
        'is_active' => 'bool',
    ];
}
```

#### RelationHandler
- Manages all relationship types
- Handles eager/lazy loading
- Maintains relationship state

```php
class User extends Model {
    public function posts() {
        return $this->hasMany(Post::class);
    }
}
```

### 2. Lifecycle Hooks
Simple, focused hooks for model events:

```php
class User extends Model {
    protected function beforeSave() {
        $this->password = hash($this->password);
    }
    
    protected function afterSave() {
        Cache::forget("user:{$this->id}");
    }
}
```

### 3. Query Scopes
Automatic query constraints through inheritance:

```php
// Base tenant model
class TenantModel extends Model {
    protected function applyScope(Query $query) {
        $query->where('tenant_id', getCurrentTenant());
    }
}

// Scoped model
class User extends TenantModel {
    protected $table = 'users';
}
```

## Design Principles

1. **Single Responsibility**
   - Each handler has a focused purpose
   - Clear separation between data, relations, and behavior
   - Hooks handle lifecycle, scopes handle visibility

2. **Safe by Default**
   - Scopes automatically applied to all queries
   - Can't accidentally bypass tenant isolation
   - Type casting always enforced

3. **Clean API**
   - No magic methods
   - Explicit over implicit
   - Consistent patterns

4. **Extensible Architecture**
   - Easy to add new handlers
   - Hooks provide extension points
   - Inheritance for common patterns

## Examples

### Multi-tenant Application
```php
// Base tenant model
class TenantModel extends Model {
    protected function applyScope(Query $query) {
        $query->where('tenant_id', getCurrentTenant());
    }
}

// User model with scope and hooks
class User extends TenantModel {
    protected $casts = ['settings' => 'json'];
    
    protected function beforeSave() {
        $this->validate();
    }
    
    public function profile() {
        return $this->hasOne(Profile::class);
    }
}

// Usage
$users = User::query()->with('profile')->all();  // Scoped to tenant
```

### Admin Access
```php
// Unscoped model for admin
class AdminUser extends Model {
    protected $table = 'users';
}

// Usage
$allUsers = AdminUser::query()->all();  // All users
```
