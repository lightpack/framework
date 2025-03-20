# Model Best Practices

## Model Boundaries & Guidelines

### 1. Models Are Data Objects
Models should ONLY handle:
- Data attributes via AttributeHandler
- Relationships via RelationHandler
- Basic persistence (save/delete)
- Query scoping for data visibility

```php
// GOOD: Model just represents data
class User extends Model {
    protected $table = 'users';
    protected $casts = ['settings' => 'json'];
    
    public function profile() {
        return $this->hasOne(Profile::class);
    }
}
```

### 2. Business Logic Goes in Services
```php
// GOOD: Business logic in service
class UserService {
    public function register(User $user, array $data) {
        $user->fill($data);
        $this->validate($user);
        $this->hashPassword($user);
        $user->save();
        $this->sendWelcomeEmail($user);
    }
}

// BAD: Business logic in model
class User extends Model {
    public function register(array $data) {  // Don't do this!
        $this->fill($data);
        $this->validate();
        $this->hashPassword();
        $this->save();
        $this->sendWelcomeEmail();
    }
}
```

### 3. Query Logic Goes in Repositories
```php
// GOOD: Complex queries in repository
class UserRepository {
    public function findActiveSubscribers(): array {
        return User::query()
            ->where('active', true)
            ->whereHas('subscriptions')
            ->all();
    }
}

// BAD: Complex queries in controller
$users = User::query()
    ->where('active', true)
    ->whereHas('subscriptions')
    ->all();
```

### 4. Use Domain Objects for Complex Logic
```php
// GOOD: Rich domain object
class UserProfile {
    private User $user;
    
    public function getDisplayName(): string {
        return $this->user->first_name . ' ' . $this->user->last_name;
    }
    
    public function isComplete(): bool {
        return !empty($this->user->email) && 
               !empty($this->user->phone);
    }
}

// BAD: Logic in model
class User extends Model {
    public function getDisplayName() {  // Don't do this!
        return "{$this->first_name} {$this->last_name}";
    }
}
```

### 5. Keep Magic to a Minimum
- `__get/__set` only for simple attribute access
- No magic method calls
- No dynamic scopes
- No global scopes
- Explicit is better than implicit

### 6. Use Composition Over Inheritance
```php
// GOOD: Compose functionality
class UserService {
    private UserRepository $repository;
    private PasswordHasher $hasher;
    private Mailer $mailer;
    
    public function register(array $data) {
        $user = $this->repository->create($data);
        $this->hasher->hash($user);
        $this->mailer->sendWelcome($user);
    }
}

// BAD: Inherit for functionality
class User extends Model {
    use HasPassword, Mailable, Validatable; // Don't do this!
}
```

## Case Study: E-commerce Order System

Let's look at a real-world example of processing orders in an e-commerce system.

### 1. Models (Data Layer)

```php
// Just data and relationships
class Order extends Model {
    protected $table = 'orders';
    protected $casts = [
        'total' => 'float',
        'meta' => 'json',
        'paid_at' => 'datetime'
    ];
    
    public function items() {
        return $this->hasMany(OrderItem::class);
    }
    
    public function customer() {
        return $this->belongsTo(Customer::class);
    }
}

class OrderItem extends Model {
    protected $table = 'order_items';
    protected $casts = [
        'price' => 'float',
        'quantity' => 'int'
    ];
    
    public function product() {
        return $this->belongsTo(Product::class);
    }
    
    public function order() {
        return $this->belongsTo(Order::class);
    }
}
```

### 2. Repository (Query Layer)

```php
class OrderRepository {
    public function findPendingOrders(): array {
        return Order::query()
            ->whereNull('paid_at')
            ->with(['items.product', 'customer'])
            ->all();
    }
    
    public function findByCustomer(Customer $customer): array {
        return Order::query()
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'DESC')
            ->all();
    }
    
    public function create(array $data): Order {
        $order = new Order;
        $order->fill($data);
        $order->save();
        return $order;
    }
}
```

### 3. Domain Objects (Business Logic)

```php
class OrderProcessor {
    private Order $order;
    
    public function __construct(Order $order) {
        $this->order = $order;
    }
    
    public function calculateTotal(): float {
        return $this->order->items->sum(function($item) {
            return $item->price * $item->quantity;
        });
    }
    
    public function validate(): bool {
        return $this->hasItems() && 
               $this->hasValidCustomer() &&
               $this->hasStock();
    }
    
    public function hasStock(): bool {
        foreach($this->order->items as $item) {
            if ($item->product->stock < $item->quantity) {
                return false;
            }
        }
        return true;
    }
}
```

### 4. Service Layer (Orchestration)

```php
class OrderService {
    private OrderRepository $repository;
    private PaymentGateway $payment;
    private Mailer $mailer;
    
    public function process(Order $order) {
        // Validate
        $processor = new OrderProcessor($order);
        if (!$processor->validate()) {
            throw new InvalidOrderException();
        }
        
        // Calculate
        $order->total = $processor->calculateTotal();
        
        // Process Payment
        $this->payment->charge($order);
        $order->paid_at = now();
        
        // Save
        $this->repository->save($order);
        
        // Notify
        $this->mailer->sendOrderConfirmation($order);
    }
    
    public function refund(Order $order) {
        // Business logic for refunds
    }
}
```

### 5. Controller (HTTP Layer)

```php
class OrderController {
    private OrderService $service;
    private OrderRepository $repository;
    
    public function store(Request $request) {
        $order = $this->repository->create($request->all());
        
        try {
            $this->service->process($order);
            return redirect()->with('success', 'Order processed');
        } catch (InvalidOrderException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
```

### Benefits of This Architecture

1. **Clear Responsibilities**
   - Models: Just data and relationships
   - Repository: Database queries
   - Domain Objects: Business rules
   - Service: Orchestration
   - Controller: HTTP handling

2. **Easy to Test**
```php
class OrderProcessorTest extends TestCase {
    public function testCalculatesTotal() {
        $order = new Order;
        // ... setup test data ...
        $processor = new OrderProcessor($order);
        $this->assertEquals(100.00, $processor->calculateTotal());
    }
}
```

3. **Maintainable**
   - Each class is focused and small
   - Easy to find where code should go
   - Easy to modify one aspect without touching others

4. **Scalable**
   - New features just add new classes
   - No need to modify existing code
   - Easy to add new business rules

This case study shows how to build a real system while keeping models clean and focused on their core responsibility: data management.

## Benefits of This Approach

1. **Maintainable Code**
   - Clear separation of concerns
   - Each class has a single responsibility
   - Easy to test in isolation

2. **Scalable Architecture**
   - Business logic properly organized
   - No "God" models
   - Easy to add new features

3. **Team Friendly**
   - Clear conventions
   - Predictable code organization
   - Easy onboarding

4. **Testable**
   - Services can be mocked
   - Models are thin
   - Logic is isolated

## Case Study 2: Blog CMS

### Context & Requirements

We're building a multi-tenant blog CMS with these requirements:
1. Multiple blogs (tenants) on single platform
2. Each blog has posts, categories, and tags
3. Posts can be drafts or published
4. Posts need SEO metadata
5. Support for scheduled publishing
6. Track post views and engagement
7. Comment system with moderation
8. Rich text content with media handling

### Database Schema

```sql
-- Blogs (tenants)
CREATE TABLE blogs (
    id INT PRIMARY KEY,
    domain VARCHAR(255),
    name VARCHAR(255),
    settings JSON
);

-- Posts
CREATE TABLE posts (
    id INT PRIMARY KEY,
    blog_id INT,
    title VARCHAR(255),
    slug VARCHAR(255),
    content TEXT,
    status ENUM('draft', 'published'),
    published_at DATETIME,
    seo_title VARCHAR(255),
    seo_description TEXT,
    FOREIGN KEY (blog_id) REFERENCES blogs(id)
);

-- Categories
CREATE TABLE categories (
    id INT PRIMARY KEY,
    blog_id INT,
    name VARCHAR(255),
    slug VARCHAR(255),
    FOREIGN KEY (blog_id) REFERENCES blogs(id)
);

-- Tags
CREATE TABLE tags (
    id INT PRIMARY KEY,
    blog_id INT,
    name VARCHAR(255),
    slug VARCHAR(255),
    FOREIGN KEY (blog_id) REFERENCES blogs(id)
);

-- Post Categories & Tags
CREATE TABLE post_category (
    post_id INT,
    category_id INT,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE post_tag (
    post_id INT,
    tag_id INT,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (tag_id) REFERENCES tags(id)
);
```

### 1. Models (Data Layer)

```php
// Base tenant model with scope
class BlogModel extends TenantModel {
    protected function applyScope(Query $query) {
        $query->where('blog_id', getCurrentBlog()->id);
    }
}

// Just data and relationships
class Post extends BlogModel {
    protected $table = 'posts';
    protected $casts = [
        'published_at' => 'datetime',
        'status' => PostStatus::class,  // Custom cast
    ];
    
    public function categories() {
        return $this->belongsToMany(Category::class);
    }
    
    public function tags() {
        return $this->belongsToMany(Tag::class);
    }
}

class Category extends BlogModel {
    protected $table = 'categories';
    
    public function posts() {
        return $this->belongsToMany(Post::class);
    }
}

class Tag extends BlogModel {
    protected $table = 'tags';
    
    public function posts() {
        return $this->belongsToMany(Post::class);
    }
}
```

### 2. Value Objects & Enums

```php
// Custom cast for post status
class PostStatus {
    private string $status;
    
    public function __construct(string $status) {
        if (!in_array($status, ['draft', 'published'])) {
            throw new InvalidArgumentException('Invalid status');
        }
        $this->status = $status;
    }
    
    public function isDraft(): bool {
        return $this->status === 'draft';
    }
    
    public function isPublished(): bool {
        return $this->status === 'published';
    }
    
    public function __toString(): string {
        return $this->status;
    }
}

// Rich content handling
class Content {
    private string $html;
    
    public function sanitize(): void {
        // Clean HTML
    }
    
    public function extractImages(): array {
        // Get image references
    }
}
```

### 3. Repositories (Query Layer)

```php
class PostRepository {
    public function findPublished(): array {
        return Post::query()
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'DESC')
            ->with(['categories', 'tags'])
            ->all();
    }
    
    public function findBySlug(string $slug): ?Post {
        return Post::query()
            ->where('slug', $slug)
            ->with(['categories', 'tags'])
            ->one();
    }
    
    public function findScheduled(): array {
        return Post::query()
            ->where('status', 'published')
            ->where('published_at', '>', now())
            ->orderBy('published_at', 'ASC')
            ->all();
    }
}
```

### 4. Domain Services (Business Logic)

```php
class PostPublisher {
    private Post $post;
    private MediaHandler $media;
    
    public function publish(): void {
        // Validate
        if ($this->post->title === '') {
            throw new ValidationException('Title required');
        }
        
        // Process content
        $content = new Content($this->post->content);
        $content->sanitize();
        
        // Handle media
        foreach ($content->extractImages() as $image) {
            $this->media->process($image);
        }
        
        // Update post
        $this->post->status = new PostStatus('published');
        $this->post->published_at = now();
        $this->post->save();
    }
}

class SlugGenerator {
    public function generate(Post $post): string {
        $base = Str::slug($post->title);
        $slug = $base;
        $counter = 1;
        
        while ($this->slugExists($slug, $post->id)) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }
        
        return $slug;
    }
}
```

### 5. Application Services (Orchestration)

```php
class PostService {
    private PostRepository $posts;
    private PostPublisher $publisher;
    private SlugGenerator $slugs;
    private SearchIndexer $search;
    
    public function create(array $data): Post {
        // Create post
        $post = new Post;
        $post->fill($data);
        
        // Generate slug
        $post->slug = $this->slugs->generate($post);
        
        // Save
        $post->save();
        
        // Handle relations
        if (isset($data['categories'])) {
            $post->categories()->attach($data['categories']);
        }
        
        return $post;
    }
    
    public function publish(Post $post): void {
        // Business logic
        $this->publisher->publish($post);
        
        // Side effects
        $this->search->index($post);
        $this->notifySubscribers($post);
    }
}
```

### 6. Controllers (HTTP Layer)

```php
class PostController {
    private PostService $posts;
    
    public function store(Request $request) {
        try {
            $post = $this->posts->create($request->all());
            
            if ($request->get('publish')) {
                $this->posts->publish($post);
            }
            
            return redirect()
                ->route('posts.edit', $post)
                ->with('success', 'Post created');
                
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
```

### Benefits Demonstrated

1. **Clean Model Layer**
   - Models just define data structure and relationships
   - No business logic in models
   - Multi-tenancy handled via inheritance
   - Type safety via custom casts

2. **Rich Domain Model**
   - Value objects for complex types (PostStatus)
   - Domain services for business rules
   - Clear boundaries between layers

3. **Flexible Query Layer**
   - Repository handles all query complexity
   - Easy to add new query patterns
   - Consistent eager loading

4. **Maintainable Services**
   - Each service has single responsibility
   - Easy to add new features
   - Clear orchestration of operations

5. **Testing Strategy**
```php
class PostPublisherTest extends TestCase {
    public function testValidatesTitle() {
        $post = new Post(['title' => '']);
        $publisher = new PostPublisher($post);
        
        $this->expectException(ValidationException::class);
        $publisher->publish();
    }
    
    public function testGeneratesUniqueSlug() {
        $generator = new SlugGenerator();
        
        $post1 = new Post(['title' => 'Hello World']);
        $post2 = new Post(['title' => 'Hello World']);
        
        $this->assertEquals('hello-world', $generator->generate($post1));
        $this->assertEquals('hello-world-1', $generator->generate($post2));
    }
}
```

This case study demonstrates:
1. How to handle complex domain rules
2. Multi-tenant data isolation
3. Rich content processing
4. Scheduled operations
5. Clean separation of concerns
6. Type safety and validation
7. Proper testing approach

Each component has a clear responsibility and the models remain focused on their core purpose: data structure and relationships.

## Understanding the Architecture

### Key Concepts & Terminology

#### 1. Models (What, When, Why)
- **What**: Simple classes that represent database tables and their relationships
- **When**: Use when you need to interact with database tables
- **Why**: To provide a clean interface for data access and relationships
- **Where**: In the data layer, closest to the database
- **How**: Extend `Model` class, define table and relationships only

```php
// THIS is a model's job
class Post extends Model {
    protected $table = 'posts';
    protected $casts = ['published_at' => 'datetime'];
    
    public function author() {
        return $this->belongsTo(User::class);
    }
}

// NOT a model's job
class Post extends Model {
    public function publish() {        // Business logic
    public function validateTitle() {  // Validation
    public function toSearchIndex() {  // External concerns
}
```

#### 2. Value Objects (What, When, Why)
- **What**: Small, immutable objects representing a domain concept
- **When**: Use when a simple value needs validation or behavior
- **Why**: To encapsulate validation and provide type safety
- **Where**: In the domain layer, used by models and services
- **How**: Create plain PHP classes with validation in constructor

```php
// Good Value Object
class Email {
    private string $address;
    
    public function __construct(string $address) {
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email');
        }
        $this->address = $address;
    }
    
    public function domain(): string {
        return substr(strrchr($this->address, "@"), 1);
    }
}

// Usage
$user->email = new Email('john@example.com');  // Type-safe, validated
```

#### 3. Repositories (What, When, Why)
- **What**: Classes that encapsulate database queries
- **When**: Use when you have complex queries or need to reuse queries
- **Why**: To keep query logic in one place and make it reusable
- **Where**: In the data access layer, between models and services
- **How**: Create methods that return models or collections

```php
// Good Repository
class PostRepository {
    public function findPublished(): array {
        return Post::query()
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'DESC')
            ->all();
    }
}

// BAD: Don't scatter queries
class PostController {
    public function index() {
        $posts = Post::query()  // Complex query in controller
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'DESC')
            ->all();
    }
}

class RssController {
    public function feed() {
        // Same query duplicated! 
        $posts = Post::query()
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'DESC')
            ->all();
    }
}
```

#### 4. Domain Services (What, When, Why)
- **What**: Classes that implement business rules
- **When**: Use when an operation doesn't naturally belong to a single model
- **Why**: To keep business logic organized and testable
- **Where**: In the domain layer, used by application services
- **How**: Create focused classes for specific business operations

```php
// Good Domain Service
class OrderProcessor {
    public function process(Order $order): void {
        $this->validateStock($order);
        $this->calculateTotal($order);
        $this->applyDiscount($order);
    }
}

// BAD: Don't put business logic in models
class Order extends Model {
    public function process() {  // Complex operation in model
        $this->validateStock();
        $this->calculateTotal();
        $this->applyDiscount();
    }
}
```

#### 5. Application Services (What, When, Why)
- **What**: Classes that orchestrate multiple operations
- **When**: Use when you need to coordinate multiple steps
- **Why**: To provide a single entry point for complex operations
- **Where**: Between controllers and domain layer
- **How**: Inject dependencies, coordinate steps

```php
// Good Application Service
class PostService {
    public function __construct(
        private PostRepository $posts,
        private SlugGenerator $slugs,
        private SearchIndexer $search
    ) {}
    
    public function create(array $data): Post {
        // Orchestrates the creation process
        $post = $this->posts->create($data);
        $post->slug = $this->slugs->generate($post);
        $this->search->index($post);
        return $post;
    }
}

// BAD: Don't orchestrate in controllers
class PostController {
    public function store(Request $request) {  // Too much responsibility
        $post = new Post($request->all());
        $post->slug = Str::slug($post->title);
        $post->save();
        SearchIndex::update($post);
    }
}
```

### Common Anti-Patterns to Avoid

1. **Fat Models**
```php
// BAD: Model doing everything
class User extends Model {
    public function register() {}    // Business logic
    public function suspend() {}     // Business logic
    public function sendEmail() {}   // External service
    public function toJson() {}      // Presentation
}

// GOOD: Keep models focused on data
class User extends Model {
    protected $table = 'users';
    protected $casts = ['settings' => 'json'];
    
    public function profile() {
        return $this->hasOne(Profile::class);
    }
}
```

2. **Anemic Services**
```php
// BAD: Just passing through
class UserService {
    public function create(array $data) {
        return User::create($data);  // No value added
    }
}

// GOOD: Add business value
class UserService {
    public function register(array $data) {
        $user = $this->users->create($data);
        $this->passwordHasher->hash($user);
        $this->roles->assignDefault($user);
        $this->mailer->sendWelcome($user);
        return $user;
    }
}
```

3. **Logic in Controllers**
```php
// BAD: Business logic in controllers
class OrderController {
    public function process(Order $order) {
        if ($order->items->isEmpty()) {
            throw new ValidationException();
        }
        
        $total = $order->items->sum('price');
        if ($total > 1000) {
            $discount = $total * 0.1;
        }
        // More business logic...
    }
}

// GOOD: Controllers should delegate
class OrderController {
    public function process(Order $order) {
        $this->orderService->process($order);
        return redirect()->with('success', 'Order processed');
    }
}
```

### Decision Making Guide

1. **Where to Put New Code?**
   - Data structure/relationships → Model
   - Complex queries → Repository
   - Business rules → Domain Service
   - Coordination → Application Service
   - HTTP/Request handling → Controller

2. **When to Create a Service?**
   - Operation involves multiple models
   - Complex business rules
   - External service integration
   - Need to maintain transaction boundary
   - Code reuse across controllers

3. **When to Use Value Objects?**
   - Need validation for a value
   - Value has its own behavior
   - Want type safety
   - Concept appears in multiple places

4. **When to Use Repository?**
   - Same query used in multiple places
   - Complex query conditions
   - Need to abstract query logic
   - Want to make queries testable

This architecture promotes:
- Single Responsibility Principle
- Separation of Concerns
- Don't Repeat Yourself (DRY)
- Easy Testing
- Clear Boundaries
- Maintainable Code

## Real-World Challenges & Common Pitfalls

### Why Developers Often Skip Best Practices

1. **Time Pressure**
   - "We need this yesterday!"
   - Quick fixes become permanent
   - Technical debt accumulates
   ```php
   // Under pressure: Quick but messy
   class OrderController {
       public function process() {
           $order = Order::find($id);
           // Business logic scattered in controller 😱
           if ($order->items->isEmpty()) return;
           $order->status = 'processing';
           $order->save();
           Mail::send('order_confirmation', $order);
       }
   }
   ```

2. **Perceived Complexity**
   - "It's just a simple CRUD app"
   - "We can refactor later" (but never do)
   - "This is overengineering"
   ```php
   // Seems simpler at first
   class User extends Model {
       public function suspend() {
           $this->active = false;
           $this->save();
       }
   }
   
   // Until requirements change...
   class User extends Model {
       public function suspend() {
           $this->active = false;
           $this->save();
           $this->notifyAdmin();  // Added later
           Cache::forget("user.{$this->id}");  // Added later
           event(new UserSuspended($this));  // Added later
           // Growing mess...
       }
   }
   ```

3. **Framework Influence**
   - "But Laravel does it this way"
   - Following patterns without understanding why
   - Mixing framework and domain code
   ```php
   // Framework bleeding into domain
   class Post extends Model {
       use HasFactory, Notifiable, SoftDeletes;
       
       protected $fillable = ['title', 'content'];
       
       // Framework features mixed with business logic
       public function publish() {
           $this->fill(['status' => 'published']);
           $this->save();
           $this->notify(new PostPublished);
       }
   }
   ```

4. **Team Dynamics**
   - Junior developers need guidance
   - Different skill levels in team
   - Inconsistent code review standards
   ```php
   // Different styles in same codebase
   class OrderService {
       public function process() { /* Clean code */ }
   }
   
   class UserController {
       public function suspend() { /* Messy code */ }
   }
   ```

### How to Address These Challenges

1. **Make It Easy to Do Right**
   ```php
   // Provide base classes and traits
   abstract class BaseRepository {
       protected function query() {
           return $this->model->query();
       }
       
       public function find($id) {
           return $this->query()->find($id);
       }
   }
   
   // Easy to implement correctly
   class UserRepository extends BaseRepository {
       protected $model = User::class;
   }
   ```

2. **Document the Why, Not Just the How**
   ```php
   // Clear comments explaining rationale
   class OrderProcessor {
       /**
        * Process handles complex order logic including:
        * - Stock validation
        * - Payment processing
        * - Notification
        * 
        * We use a separate class (not in Order model) because:
        * 1. Single Responsibility Principle
        * 2. Easier to test
        * 3. Can be reused in different contexts
        */
        public function process(Order $order) { }
   }
   ```

3. **Incremental Improvements**
   ```php
   // Start simple but structured
   class UserRepository {
       // Phase 1: Basic CRUD
       public function find($id) { }
       public function save(User $user) { }
       
       // Phase 2: Add caching later
       public function find($id) {
           return Cache::remember("user.{$id}", fn() => /* ... */);
       }
       
       // Phase 3: Add events later
       public function save(User $user) {
           $result = parent::save($user);
           event(new UserSaved($user));
           return $result;
       }
   }
   ```

4. **Clear Code Review Guidelines**
   - No business logic in controllers
   - No query logic in services
   - No external dependencies in models
   - Document exceptions to rules

5. **Team Training**
   - Regular code reviews
   - Pair programming sessions
   - Architecture decision records
   - Knowledge sharing sessions

### Remember
- Perfect is the enemy of good
- Start with simple but clean structure
- Refactor gradually
- Build team consensus
- Document decisions and rationale
- Keep learning and improving

The key is finding the right balance between pragmatism and best practices. Don't let perfect architecture prevent you from shipping, but also don't let short-term pressure create long-term problems.
