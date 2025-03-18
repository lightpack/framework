# Attribute Casting in Lightpack ORM

Lightpack ORM provides a powerful attribute casting system that automatically converts database values to PHP native types and vice versa. This ensures type consistency throughout your application and makes working with different data types seamless.

## Basic Usage

To enable attribute casting for your model, define the `$casts` property with a mapping of attributes to their desired types:

```php
class User extends Model
{
    protected array $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'birth_date' => 'date',
        'last_login' => 'datetime',
        'login_count' => 'integer',
        'score' => 'float'
    ];
}
```

Now, whenever you access these attributes, they will be automatically cast to their specified types:

```php
$user = User::find(1);

// Automatically cast to boolean
$isActive = $user->is_active;  // true/false

// Automatically cast to array
$settings = $user->settings;   // ['theme' => 'dark', 'notifications' => true]

// Automatically cast to DateTime
$lastLogin = $user->last_login;  // DateTime object
```

## Available Cast Types

Lightpack ORM supports the following cast types:

| Cast Type | PHP Type | Description |
|-----------|----------|-------------|
| `integer`, `int` | `int` | Cast to integer |
| `float`, `double`, `real` | `float` | Cast to floating point number |
| `string` | `string` | Cast to string |
| `boolean`, `bool` | `bool` | Cast to boolean |
| `array`, `json` | `array` | Cast JSON string to array |
| `date` | `string` | Cast to Y-m-d format |
| `datetime` | `DateTimeInterface` | Cast to DateTime object |
| `timestamp` | `int` | Cast to Unix timestamp |

## Automatic Type Conversion

The casting system works bidirectionally:

1. **Database to PHP**: When retrieving attributes from the database, values are automatically cast to their specified PHP types.
2. **PHP to Database**: When saving attributes to the database, values are automatically converted back to their database-friendly format.

```php
$user = new User();

// Automatically converts array to JSON string for database
$user->settings = ['theme' => 'dark'];

// Automatically converts string to DateTime for database
$user->last_login = '2025-03-18 12:00:00';

$user->save();
```

## Handling Null Values

The casting system properly handles null values. If an attribute is null in the database:

1. It remains null when retrieved through the model
2. No type casting is performed
3. It's stored as null in the database when saved

```php
$user = User::find(1);

// If last_login is NULL in database
$lastLogin = $user->last_login;  // null, not a DateTime object
```

## Relationship Loading

When loading relationships, empty or non-existent relations return `null`:

```php
$user = User::find(1);

// Returns null if no profile exists
$profile = $user->profile;  

// Returns empty collection if no posts exist
$posts = $user->posts;  // Empty collection
```

## Best Practices

1. **Type Safety**: Always define casts for attributes that should have specific types
2. **Date Handling**: Use `datetime` or `date` casts for date fields to ensure proper formatting
3. **JSON Data**: Use `array` cast for JSON columns to work with them as native PHP arrays
4. **Validation**: While casting provides type conversion, always validate input data before saving

## Performance Considerations

The casting system is designed to be efficient:

1. Casts are only applied when accessing attributes
2. Results are cached after first access
3. Bulk operations maintain good performance

Remember that complex casts (like JSON/array) have more overhead than simple casts (like integer/boolean).
