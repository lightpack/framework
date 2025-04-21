# Lightpack Uploads

A flexible, model-based file upload system for the Lightpack framework.

## Features

- Model-specific file **attachments**
- Support for single and multiple file uploads
- Remote file uploads from URLs
- Image transformations (resize)
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

Add the `UploadTrait` trait to any model that needs file upload capabilities:

```php
use Lightpack\Uploads\UploadTrait;

class User extends Model
{
    use UploadTrait;
    
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
        'medium' => [
            'resize' => [400, 400],
        ],
    ],
]);
```

Then access the transformed versions:

```php
$thumbnailUrl = $avatar->url('thumbnail');
$mediumUrl = $avatar->url('medium');
```

### How Transformations Work

Transformations are processed by the `TransformJob` class, which:

1. Loads the original image
2. Applies the requested transformations
3. Saves the transformed versions to the appropriate storage location

You can specify different dimensions for each variant:

```php
'transformations' => [
    'thumbnail' => [
        'resize' => [200, 200],
    ],
    'banner' => [
        'resize' => [1200, 300],
    ],
]
```

## File Storage System

### Storage Disks

The Uploads module uses the concept of "disks" to determine where files are stored:

```php
$user->attach('document', [
    'collection' => 'documents',
    'disk' => 'private', // Use a private disk instead of public
]);
```

Available disks:
- `public` (default) - For publicly accessible files
- `private` - For files that require authentication
- `s3` - For files stored on Amazon S3
- Custom disks defined in your storage configuration

### Listing Files in a Directory

The Storage system provides a `files()` method to list all files in a directory:

```php
$storage = app('storage');
$files = $storage->files('uploads/public/media/123');

foreach ($files as $file) {
    // Process each file
}
```

This works consistently across different storage backends (local filesystem, S3, etc.).

### Customizing Upload Paths

By default, files are stored in `uploads/{disk}/{model_id}/{filename}`, but you can customize this:

```php
$user->attach('avatar', [
    'collection' => 'profile',
    'path' => 'users/' . $user->id . '/avatars',
]);
```

## Metadata and Validation

### File Metadata

The UploadModel stores comprehensive metadata about each file:

```php
$upload = $user->firstUpload('documents');

// Basic metadata
$filename = $upload->file_name;
$extension = $upload->getExtension();
$mimeType = $upload->getMimeType();
$size = $upload->getSize();

// Custom metadata
$upload->meta = [
    'title' => 'My Document',
    'description' => 'Important file',
    'tags' => ['important', 'document'],
];
$upload->save();

// Later, retrieve custom metadata
$title = $upload->getMeta('title');
$tags = $upload->getMeta('tags', []);
```

### Validation

While the Uploads module doesn't include built-in validation, you can easily add it:

```php
public function uploadDocument(Request $request)
{
    // Validate the upload
    $validator = new Validator($request->files);
    $validator->rule('required', 'document');
    $validator->rule('mimes', 'document', ['pdf', 'doc', 'docx']);
    $validator->rule('max', 'document', 5 * 1024 * 1024); // 5MB
    
    if (!$validator->validate()) {
        return redirect()->back()->withErrors($validator->errors());
    }
    
    // Process the upload
    $this->user->attach('document', [
        'collection' => 'documents',
    ]);
    
    return redirect()->back()->withSuccess('Document uploaded successfully.');
}
```

## Advanced Configuration

### Storage Configuration

The Uploads module uses Lightpack's Storage system. Make sure your storage configuration is properly set up in your config files.

### Error Handling

The upload methods can throw exceptions in case of errors:

```php
try {
    $upload = $user->attach('avatar', [
        'collection' => 'profile',
    ]);
} catch (FileUploadException $e) {
    // Handle upload errors
    return redirect()->back()->withError('Failed to upload file: ' . $e->getMessage());
} catch (\Exception $e) {
    // Handle other errors
    return redirect()->back()->withError('An unexpected error occurred.');
}
```

## License

This module is part of the Lightpack Framework and is subject to the same license terms.
