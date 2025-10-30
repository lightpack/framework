# Route Model Binding - Quick Reference Guide

## What is Route Model Binding?

Route Model Binding automatically loads model instances from route parameters, eliminating boilerplate code.

## Basic Example

### Before (Manual)
```php
route()->get('/users/:id', UserController::class, 'show');

class UserController {
    public function show($id) {
        $user = User::query()->find($id);
        if (!$user) {
            throw new RecordNotFoundException();
        }
        return view('users.show', ['user' => $user]);
    }
}
```

### After (With Binding)
```php
route()->get('/users/:id', UserController::class, 'show')
    ->bind('id', User::class);

class UserController {
    public function show(User $id) {
        // $id is already a loaded User model!
        return view('users.show', ['user' => $id]);
    }
}
```

## API Reference

### `Route::bind(string $param, string $model, ?string $field = null): self`

Binds a route parameter to a model class.

**Parameters:**
- `$param` - Route parameter name (e.g., 'id', 'user_id')
- `$model` - Fully qualified model class name
- `$field` - (Optional) Database column to query (default: primary key)

**Returns:** `self` for method chaining

**Throws:**
- `Exception` if model class doesn't exist
- `Exception` if class doesn't extend `Model`
- `Exception` if parameter doesn't exist in route

## Common Patterns

### 1. Single Model Binding

```php
route()->get('/products/:id', ProductController::class, 'show')
    ->bind('id', Product::class);

class ProductController {
    public function show(Product $id) {
        return $id; // Product model
    }
}
```

### 2. Multiple Model Bindings

```php
route()->get('/authors/:author_id/books/:book_id', BookController::class, 'show')
    ->bind('author_id', Author::class)
    ->bind('book_id', Book::class);

class BookController {
    public function show(Author $author_id, Book $book_id) {
        // Both models loaded
    }
}
```

### 3. Custom Field Binding

```php
// Bind by slug instead of ID
route()->get('/products/:slug', ProductController::class, 'show')
    ->bind('slug', Product::class, 'slug');

class ProductController {
    public function show(Product $slug) {
        // Product loaded by slug field
    }
}
```

### 4. Mixed Parameters

```php
route()->get('/products/:id/category/:category', ProductController::class, 'filter')
    ->bind('id', Product::class);

class ProductController {
    public function filter(Product $id, $category) {
        // $id is Product model
        // $category is string
    }
}
```

### 5. With Filters and Names

```php
route()->put('/products/:id', ProductController::class, 'update')
    ->bind('id', Product::class)
    ->filter('csrf')
    ->filter('auth')
    ->name('products.update');
```

## Important Rules

### ⚠️ Parameter Names Must Match

Controller parameter names **MUST** match route parameter names:

```php
// ✅ CORRECT
route()->get('/users/:id', UserController::class, 'show')
    ->bind('id', User::class);

class UserController {
    public function show(User $id) { } // ✅ Matches 'id'
}

// ❌ WRONG
route()->get('/users/:id', UserController::class, 'show')
    ->bind('id', User::class);

class UserController {
    public function show(User $user) { } // ❌ Doesn't match 'id'
}
```

### ⚠️ Model Not Found = 404

If the model isn't found, `RecordNotFoundException` is thrown:

```php
// GET /users/999 (doesn't exist)
// Throws: RecordNotFoundException
// Should be caught and converted to 404 by your error handler
```

### ⚠️ Validation Happens at Boot Time

Errors are caught when routes are loaded, not during requests:

```php
// This fails immediately when App::bootRoutes() runs
route()->get('/users/:id', UserController::class, 'show')
    ->bind('id', NonExistentModel::class); // ❌ Exception thrown here
```

## Performance

### Zero Overhead When Not Used
Routes without `->bind()` have zero performance impact (single boolean check).

### One Query Per Binding
Each bound model executes one database query (same as manual implementation).

### Automatic Caching
Models are cached per-request to prevent duplicate queries.

## Real-World Examples

### Blog Application

```php
// List all posts
route()->get('/posts', PostController::class, 'index');

// Show single post by slug
route()->get('/posts/:slug', PostController::class, 'show')
    ->bind('slug', Post::class, 'slug');

// Edit post (requires auth)
route()->get('/posts/:id/edit', PostController::class, 'edit')
    ->bind('id', Post::class)
    ->filter('auth');

// Show comment on post
route()->get('/posts/:post_id/comments/:comment_id', CommentController::class, 'show')
    ->bind('post_id', Post::class)
    ->bind('comment_id', Comment::class);
```

### E-commerce Application

```php
// Product by ID
route()->get('/products/:id', ProductController::class, 'show')
    ->bind('id', Product::class);

// Product by SKU
route()->get('/sku/:sku', ProductController::class, 'showBySku')
    ->bind('sku', Product::class, 'sku');

// Order details
route()->get('/orders/:order_id', OrderController::class, 'show')
    ->bind('order_id', Order::class)
    ->filter('auth');

// Order item
route()->get('/orders/:order_id/items/:item_id', OrderItemController::class, 'show')
    ->bind('order_id', Order::class)
    ->bind('item_id', OrderItem::class)
    ->filter('auth');
```

### User Management

```php
// User profile by username
route()->get('/@:username', UserController::class, 'profile')
    ->bind('username', User::class, 'username');

// User settings
route()->get('/users/:id/settings', UserController::class, 'settings')
    ->bind('id', User::class)
    ->filter('auth');

// User's posts
route()->get('/users/:user_id/posts', PostController::class, 'userPosts')
    ->bind('user_id', User::class);
```

## Troubleshooting

### Model is null in controller

**Cause:** Parameter name mismatch  
**Solution:** Ensure controller parameter name matches route parameter name

```php
// Route parameter is 'id'
route()->get('/users/:id', UserController::class, 'show')
    ->bind('id', User::class);

// Controller parameter must also be 'id'
public function show(User $id) { } // ✅
```

### "Model class not found" error

**Cause:** Model class doesn't exist or wrong namespace  
**Solution:** Use fully qualified class name

```php
// ❌ Wrong
->bind('id', 'User')

// ✅ Correct
->bind('id', App\Models\User::class)
```

### "Must extend Model" error

**Cause:** Class is not a Lucid Model  
**Solution:** Ensure class extends `Lightpack\Database\Lucid\Model`

```php
use Lightpack\Database\Lucid\Model;

class User extends Model {
    protected $table = 'users';
}
```

### "Parameter not found in route" error

**Cause:** Binding parameter that doesn't exist in route URI  
**Solution:** Check route URI has the parameter

```php
// ❌ Wrong - no :user_id in route
route()->get('/users/:id', ...)
    ->bind('user_id', User::class);

// ✅ Correct
route()->get('/users/:id', ...)
    ->bind('id', User::class);
```

## Best Practices

### 1. Use Descriptive Parameter Names

```php
// ✅ Good
route()->get('/posts/:post_id/comments/:comment_id', ...)
    ->bind('post_id', Post::class)
    ->bind('comment_id', Comment::class);

// ❌ Confusing
route()->get('/posts/:id/comments/:id2', ...)
```

### 2. Bind Only What You Need

```php
// ✅ Good - only bind what controller uses
route()->get('/posts/:id/edit', PostController::class, 'edit')
    ->bind('id', Post::class);

// ❌ Wasteful - binding unused parameter
route()->get('/posts/:id/views', PostController::class, 'incrementViews')
    ->bind('id', Post::class); // If you only need the ID, don't bind
```

### 3. Use Custom Fields for Slugs

```php
// ✅ Good - SEO-friendly URLs
route()->get('/blog/:slug', PostController::class, 'show')
    ->bind('slug', Post::class, 'slug');

// ❌ Less friendly
route()->get('/blog/:id', PostController::class, 'show')
    ->bind('id', Post::class);
```

### 4. Combine with Filters

```php
route()->put('/posts/:id', PostController::class, 'update')
    ->bind('id', Post::class)
    ->filter('auth')      // Ensure user is logged in
    ->filter('csrf')      // CSRF protection
    ->name('posts.update');
```

## Limitations

1. **No automatic relationship validation** - If you bind both Post and Comment, there's no automatic check that the comment belongs to the post
2. **Parameter names must match** - Can't use different names in route and controller
3. **One query per binding** - Can't optimize multiple bindings into a single query
4. **No nested bindings** - Can't bind relationships (e.g., `$post->author`)

## When NOT to Use

- **List/index routes** - No model to bind
- **When you only need the ID** - Don't bind if you're just passing ID to a query
- **Complex queries** - If you need custom WHERE clauses, do it manually
- **Performance-critical paths** - If every microsecond counts, manual is faster

## Summary

Route Model Binding is:
- ✅ **Explicit** - Declared at route level
- ✅ **Fast** - Zero overhead when not used
- ✅ **Safe** - Validation at boot time
- ✅ **Clean** - Less boilerplate code
- ✅ **Flexible** - Custom fields supported

Use it to make your controllers cleaner and your code more maintainable!
