# Lightpack Uploads

A flexible, model-based file upload system for the Lightpack framework.

## Features

- Model-specific file **attachments**
- Support for single and multiple file uploads
- Remote file uploads from URLs
- Image transformations (resize, crop)
- Collection-based organization
- Works with both local and cloud storage
- Clean, intuitive API

## Installation

The Uploads module is included with Lightpack framework. To set up the database table, run the migration:

```php
php lightpack migrate Lightpack\\Uploads\\Migration\\CreateUploadsTable
```

## Basic Usage

### Preparing Your Model

Add the `HasUploads` trait to any model that needs file upload capabilities:

```php
use Lightpack\Uploads\HasUploads;

class User extends Model
{
    use HasUploads;
    
    // ...
}
```

### Attaching Files

#### Single File Upload

```php
// Attach a file from a form upload
$user->attach('avatar', [
    'collection' => 'profile',
    'singleton' => true, // Only keep one file in this collection
]);
```

#### Multiple File Upload

```php
// Attach multiple files from a form upload
$photos = $user->attachMultiple('photos', [
    'collection' => 'gallery',
]);
```

#### Remote File Upload

```php
// Attach a file from a URL
$user->attachFromUrl('https://example.com/image.jpg', [
    'collection' => 'remote',
]);
```

### Retrieving Uploads

```php
// Get all uploads in a collection
$uploads = $user->uploads('profile')->get();

// Get the first upload in a collection
$avatar = $user->firstUpload('profile');
```

### Working with Upload Models

```php
// Get the URL to the file
$url = $avatar->url();

// Get the URL to a transformed version
$thumbnailUrl = $avatar->url('thumbnail');

// Get the file path
$path = $avatar->path();

// Check if a file exists
if ($avatar->exists('thumbnail')) {
    // ...
}

// Get file metadata
$mimeType = $avatar->getMimeType();
$size = $avatar->getSize();
$meta = $avatar->getMeta();
```

### Removing Uploads

```php
// Detach a specific upload
$user->detach($uploadId);
```

## Image Transformations

You can define transformations when attaching files:

```php
$user->attach('avatar', [
    'collection' => 'profile',
    'transformations' => [
        'thumbnail' => [
            'resize' => [200, 200],
        ],
        'square' => [
            'crop' => [100, 100],
        ],
    ],
]);
```

Then access the transformed versions:

```php
$thumbnailUrl = $avatar->url('thumbnail');
$squareUrl = $avatar->url('square');
```

## Advanced Configuration

### Storage Configuration

The Uploads module uses Lightpack's Storage system. Make sure your storage configuration is properly set up in your config files.

### Custom Disk

You can specify a custom disk for your uploads:

```php
$user->attach('document', [
    'collection' => 'documents',
    'disk' => 'private', // Use a private disk instead of public
]);
```

## License

This module is part of the Lightpack Framework and is subject to the same license terms.
