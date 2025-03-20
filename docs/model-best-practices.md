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
