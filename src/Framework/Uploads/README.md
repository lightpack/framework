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

Transformations are processed automatically when you attach a file with transformations defined:

1. The original image is loaded
2. Each transformation is applied
3. Transformed versions are saved to the appropriate storage location

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

The Uploads module uses Lightpack's Storage system to store files in a consistent way:

```php
// Public files (accessible via URL)
$user->attach('avatar', [
    'collection' => 'profile',
]);

// Files are stored in: uploads/public/media/{id}/filename.jpg
// And accessible via: /uploads/media/{id}/filename.jpg
```

### Public vs Private Uploads

The framework supports both public and private uploads:

1. **Public Uploads** (default)
   - Stored in `uploads/public/...`
   - Directly accessible via URL: `/uploads/...`
   - Suitable for images, public documents, etc.

2. **Private Uploads**
   - Stored in `uploads/private/...`
   - Accessible only through a controller: `/files/serve?path=...`
   - Requires authentication/authorization
   - Suitable for sensitive documents, private files, etc.

To use private uploads, you need to set up your own controller to serve the files with proper access control. The UploadModel's URL method will automatically generate the correct URL format based on the storage path.

### URL Structure

The URL structure depends on the storage location:

```php
// For public uploads
// Stored at: uploads/public/media/123/avatar.jpg
// URL: /uploads/media/123/avatar.jpg

// For transformed versions
// Stored at: uploads/public/media/123/thumbnail/avatar.jpg
// URL: /uploads/media/123/thumbnail/avatar.jpg
```

### Customizing Upload Paths

By default, files are stored in `uploads/public/media/{model_id}/{filename}`, but you can customize this:

```php
$user->attach('avatar', [
    'collection' => 'profile',
    'path' => 'users/' . $user->id . '/avatars',
]);

// Results in:
// Storage path: uploads/public/users/123/avatars/image.jpg
// URL: /uploads/users/123/avatars/image.jpg
```

This allows you to organize files in a way that makes sense for your application.

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
