# Attribute Casting in Lightpack ORM

Lightpack ORM provides a powerful attribute casting system that automatically converts database values to PHP native types and vice versa. This ensures type consistency throughout your application and makes working with different data types seamless.

## Basic Usage

To enable attribute casting for your model, define the `$casts` property with a mapping of attributes to their desired types:

```php
class User extends Model
{
    protected array $casts = [
        'is_active' => 'boolean',    // For tinyint(1) columns storing 0/1
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

// Automatically cast to boolean (0/1 → false/true)
$isActive = $user->is_active;  // true/false

// Automatically cast to array
$settings = $user->settings;   // ['theme' => 'dark', 'notifications' => true]

// Automatically cast to DateTime
$lastLogin = $user->last_login;  // DateTime object
```

## Available Cast Types

Lightpack ORM supports the following cast types:

| Cast Type | PHP Type | Description | Common DB Types |
|-----------|----------|-------------|----------------|
| `integer`, `int` | `int` | Cast to integer | `int`, `bigint` |
| `float`, `double`, `real` | `float` | Cast to floating point number | `float`, `double`, `decimal` |
| `string` | `string` | Cast to string | `varchar`, `text` |
| `boolean`, `bool` | `bool` | Cast to boolean | `tinyint(1)`, `boolean` |
| `array`, `json` | `array` | Cast JSON string to array | `json`, `text` |
| `date` | `string` | Cast to Y-m-d format | `date` |
| `datetime` | `DateTimeInterface` | Cast to DateTime object | `datetime`, `timestamp` |
| `timestamp` | `int` | Cast to Unix timestamp | `timestamp` |

## Automatic Type Conversion

The casting system works bidirectionally:

1. **Database to PHP**: When retrieving attributes from the database, values are automatically cast to their specified PHP types.
2. **PHP to Database**: When saving attributes to the database, values are automatically converted back to their database-friendly format.

```php
$user = new User();

// Boolean cast: true/false → 1/0 in database
$user->is_blocked = true;  // Stored as 1 in tinyint column

// Array cast: PHP array → JSON string in database
$user->settings = ['theme' => 'dark'];

// DateTime cast: string → datetime in database
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

## Form Handling

When working with HTML forms, the casting system handles common form input scenarios:

### Checkbox Inputs

HTML checkboxes have special behavior:
- When checked, they send value "1" or "on"
- When unchecked, they are not sent in the form data at all

The boolean cast handles this automatically:

```php
class User extends Model
{
    protected array $casts = [
        'is_blocked' => 'boolean'
    ];
}

// In your HTML:
<input type="checkbox" name="is_blocked" value="1" <?php echo $user->is_blocked ? 'checked' : ''; ?>>

// In your controller:
$user = User::find(1);

// When checkbox is checked:
$user->is_blocked = $_POST['is_blocked'];  // "1" or "on" → true → 1 in DB

// When checkbox is unchecked:
// $_POST['is_blocked'] doesn't exist
$user->is_blocked = $_POST['is_blocked'] ?? false;  // false → 0 in DB

$user->save();
```

Best practices for handling checkbox inputs:

1. Always use the null coalescing operator (`??`) to handle unchecked state:
   ```php
   $user->is_blocked = $_POST['is_blocked'] ?? false;
   ```

2. For multiple checkboxes, you can use array syntax:
   ```php
   // HTML
   <input type="checkbox" name="settings[is_blocked]" value="1">
   <input type="checkbox" name="settings[is_private]" value="1">

   // PHP
   $user->fill([
       'is_blocked' => $_POST['settings']['is_blocked'] ?? false,
       'is_private' => $_POST['settings']['is_private'] ?? false,
   ]);
   ```

3. The casting system will handle these conversions:
   - "1", "true", "on", 1 → true → 1 in DB
   - "0", "false", "", 0, null, false → false → 0 in DB

## Best Practices

1. **Type Safety**: Always define casts for attributes that should have specific types
2. **Boolean Fields**: Use `boolean` cast for `tinyint(1)` columns storing 0/1 values
3. **Date Handling**: Use `datetime` or `date` casts for date fields to ensure proper formatting
4. **JSON Data**: Use `array` cast for JSON columns to work with them as native PHP arrays
5. **Validation**: While casting provides type conversion, always validate input data before saving

## Performance Considerations

The casting system is designed to be efficient:

1. Casts are only applied when accessing attributes
2. Results are cached after first access
3. Bulk operations maintain good performance

Remember that complex casts (like JSON/array) have more overhead than simple casts (like integer/boolean).
