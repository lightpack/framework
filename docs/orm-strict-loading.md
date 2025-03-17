# Strict Relation Loading

Lightpack ORM introduces a powerful feature called "Strict Relation Loading" that prevents N+1 query problems by design. This feature ensures optimal database performance without requiring deep ORM expertise.

## The N+1 Problem

The N+1 query problem is a common performance anti-pattern in ORMs where accessing a relation on multiple models results in multiple separate queries:

```php
// This seemingly innocent code...
$posts = Post::all();
foreach($posts as $post) {
    echo $post->author->name;  // Triggers a separate query for EACH post!
}

// ...actually executes N+1 queries:
// 1 query to fetch all posts
// N queries to fetch each post's author
```

## Lightpack's Solution: Strict Loading

Instead of allowing accidental N+1 queries, Lightpack enforces efficient relation loading through its strict mode:

```php
// This will throw an exception
$posts = Post::all();
$posts[0]->author;  // RuntimeException: Relation 'author' must be eager loaded

// This is the correct way
$posts = Post::with('author')->all();
$posts[0]->author;  // Works efficiently ✓
```

## Key Benefits

1. **Performance by Default**
   - No accidental N+1 queries
   - Optimal database performance without expert knowledge
   - Clear visibility of data requirements

2. **Better Code Quality**
   - Relations are explicitly declared
   - Data dependencies are clear and documented
   - Easier code review and maintenance

3. **Developer Friendly**
   - Clear error messages with suggestions
   - IDE support for relation declarations
   - Comprehensive debugging information

## Configuration

Enable strict mode in your models:

```php
class Post extends Model
{
    protected $strictMode = true;  // Enable strict loading
    
    // Optionally, whitelist relations that can be lazy loaded
    protected $allowedLazyRelations = ['comments'];
}
```

## Best Practices

1. **Declare Relations Upfront**
```php
// In your controller or service
$posts = Post::with(['author', 'comments'])->all();
```

2. **Use Batch Loading for Collections**
```php
// Load relations for existing collections
$posts->load('comments', 'likes');
```

3. **Keep Lazy Loading Minimal**
```php
// Only whitelist relations that truly need lazy loading
protected $allowedLazyRelations = ['userSettings'];
```

## Error Handling

When accessing non-eager-loaded relations in strict mode, a `RuntimeException` is thrown with a helpful message:

```php
RuntimeException: Strict Mode: Relation 'author' must be eager loaded. 
Use Post::with('author')->get()
```

The error message clearly indicates:
- What relation caused the error
- How to fix it using eager loading
- The correct method to use

## Development Tools

Lightpack provides helpful development tools:

1. **Relation Analysis**
   - Suggests missing eager loads
   - Identifies common relation patterns
   - Performance impact warnings

2. **Debug Information**
   - Stack traces for relation access
   - Query counts and execution times
   - Relation loading suggestions

## FAQ

**Q: Isn't this too restrictive?**
A: While it may seem restrictive at first, strict loading actually leads to better performing applications and cleaner code. It prevents problems before they occur.

**Q: What about dynamic relation loading?**
A: You can whitelist relations for lazy loading when needed using `$allowedLazyRelations`. However, we recommend keeping this list minimal.

**Q: How does this compare to other ORMs?**
A: While other ORMs allow N+1 queries by default and offer optional strict modes, Lightpack takes a "strict by default" approach to ensure optimal performance from the start.

## Migration Guide

If you're coming from other ORMs:

1. Identify commonly accessed relations
2. Use `with()` to eager load these relations
3. Use `$allowedLazyRelations` sparingly for truly dynamic cases

## Conclusion

Strict Relation Loading is a core feature of Lightpack that ensures your application performs optimally by default. While it may require slightly more upfront thought about data requirements, it prevents performance problems that are much harder to fix later.

Remember: "Make it hard to do the wrong thing, and easy to do the right thing."
