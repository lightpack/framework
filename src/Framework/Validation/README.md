# Validation

A powerful, flexible validation system for PHP applications with a fluent interface and comprehensive rule set.

## Basic Usage

```php
use Lightpack\Validation\Validator;

$validator = new Validator();

$validator
    ->field('name')
        ->required()
        ->string()
        ->max(255)
    ->field('email')
        ->required()
        ->email()
    ->field('age')
        ->required()
        ->numeric()
        ->between(18, 100);

if ($validator->validate($_POST)->isValid()) {
    // Validation passed
    $data = $validator->getData();
} else {
    // Get validation errors
    $errors = $validator->getErrors();
}
```

## Available Rules

### String Validation
```php
$validator->field('username')
    ->required()           // Field must be present
    ->string()            // Must be a string
    ->min(3)              // Minimum length
    ->max(50)             // Maximum length
    ->length(10)          // Exact length
    ->alpha()             // Only letters
    ->alphaNum()          // Letters and numbers
    ->regex('/pattern/')  // Custom pattern
    ->slug()              // URL-friendly string
```

### Numeric Validation
```php
$validator->field('age')
    ->numeric()           // Must be numeric
    ->int()              // Must be integer
    ->float()            // Must be float
    ->between(18, 100)   // Range validation
    ->min(18)            // Minimum value
    ->max(100)           // Maximum value
```

### Date Validation
```php
$validator->field('birth_date')
    ->date()                     // Must be a valid date
    ->before('2000-01-01')      // Date comparison
    ->after('1900-01-01')       // Date comparison
```

### File Validation
```php
$validator->field('document')
    ->file()                     // Basic file validation
    ->fileSize('2M')            // Max file size (B,K,M,G)
    ->fileType('application/pdf') // MIME type validation
    ->fileExtension(['pdf', 'doc']) // File extension

// Image specific validation
$validator->field('avatar')
    ->image([
        'min_width' => 100,
        'max_width' => 1000,
        'min_height' => 100,
        'max_height' => 1000,
        'ratio' => '16:9'        // Aspect ratio
    ]);

// Multiple files
$validator->field('photos')
    ->files(min: 1, max: 5)     // Number of files
    ->fileSize('2M')            // Size per file
    ->fileType(['image/jpeg', 'image/png']);
```

### Comparison Validation
```php
$validator->field('password')
    ->required()
    ->min(8)
    
$validator->field('confirm_password')
    ->required()
    ->same('password');      // Must match password

$validator->field('username')
    ->required()
    ->different('email');    // Must not match email
```

### Array Validation
```php
$validator->field('roles')
    ->array()               // Must be array
    ->in(['admin', 'user']) // Values must be in list
    ->notIn(['guest'])      // Values must not be in list
```

### Boolean Validation
```php
$validator->field('terms')
    ->bool()               // Must be boolean
    ->required();          // Must be true
```

### Custom Validation
```php
// Custom validation rule
$validator->field('code')
    ->custom(function($value) {
        return preg_match('/^CODE-\d{6}$/', $value);
    }, 'Invalid code format');

// Transform value before validation
$validator->field('tags')
    ->transform(function($value) {
        return explode(',', $value);
    })
    ->array();
```

## Error Handling

```php
// Get all errors
$errors = $validator->getErrors();

// Get errors for specific field
$nameErrors = $validator->getFieldErrors('name');

// Custom error messages
$validator->field('age')
    ->numeric()
    ->message('Age must be a number')
    ->between(18, 100)
    ->message('Age must be between 18 and 100');
```

## Wildcard Validation

Validate array fields with wildcards:

```php
// Validate each user's name and email
$validator
    ->field('users.*.name')
        ->required()
        ->string()
    ->field('users.*.email')
        ->required()
        ->email();

$data = [
    'users' => [
        ['name' => 'John', 'email' => 'john@example.com'],
        ['name' => 'Jane', 'email' => 'jane@example.com'],
    ]
];

$validator->validate($data);
```

## Real World Examples

### User Registration
```php
$validator
    ->field('username')
        ->required()
        ->string()
        ->min(3)
        ->max(50)
        ->alphaNum()
    ->field('email')
        ->required()
        ->email()
    ->field('password')
        ->required()
        ->min(8)
        ->regex('/[A-Z]/')
        ->message('Password must contain at least one uppercase letter')
        ->regex('/[0-9]/')
        ->message('Password must contain at least one number')
    ->field('avatar')
        ->nullable()
        ->image([
            'max_width' => 1000,
            'max_height' => 1000,
            'max_size' => '1M'
        ]);
```

### Blog Post
```php
$validator
    ->field('title')
        ->required()
        ->string()
        ->max(255)
    ->field('slug')
        ->required()
        ->slug()
        ->max(255)
    ->field('content')
        ->required()
        ->string()
        ->min(100)
    ->field('category_id')
        ->required()
        ->numeric()
    ->field('tags')
        ->array()
        ->transform(fn($v) => explode(',', $v))
    ->field('images')
        ->files(max: 5)
        ->fileType(['image/jpeg', 'image/png'])
        ->fileSize('2M');
```

## Contributing

Feel free to contribute to this validation system. Please read the contributing guide before submitting a pull request.

## License

This validation system is open-sourced software licensed under the MIT license.
