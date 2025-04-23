# Lightpack Uploads

A flexible, model-based file upload system for the Lightpack framework.

## Features

- Model-specific file **attachments**
- Support for single and multiple file uploads
- Remote file uploads from URLs
- Image transformations (resize)
- Collection-based organization
- Works with both local and cloud storage (S3, CloudFront)
- Public and private file storage with access control
- SEO-friendly URLs for public assets
- Clean, intuitive API

## Installation

The Uploads module is included with Lightpack framework. To set up the database table, run the migration:

```php
php lightpack migrate Lightpack\\Uploads\\Migration\\CreateUploadsTable
```

If you're upgrading to add private uploads support, run the additional migration:

```php
php lightpack migrate Lightpack\\Uploads\\Migration\\AddIsPrivateToUploadsTable
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
// Attach a file from a form upload (public)
$user->attach('avatar', [
    'collection' => 'profile',
    'singleton' => true, // Only keep one file in this collection
]);

// Attach a file as private (requires access control)
$user->attach('document', [
    'collection' => 'confidential',
    'singleton' => true,
    'private' => true,
]);
```

#### Multiple File Upload

```php
// Attach multiple files from a form upload (public)
$photos = $user->attachMultiple('photos', [
    'collection' => 'gallery',
]);

// Attach multiple files as private
$documents = $user->attachMultiple('documents', [
    'collection' => 'private-documents',
    'private' => true,
]);
```

#### Remote File Upload

```php
// Attach a file from a URL (public)
$user->attachFromUrl('https://example.com/image.jpg', [
    'collection' => 'remote',
]);

// Attach a file from a URL as private
$user->attachFromUrl('https://example.com/confidential.pdf', [
    'collection' => 'private-remote',
    'private' => true,
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
// Get the URL to the file (returns empty string for private files)
$url = $avatar->url();

// Get the URL to a transformed version
$thumbnailUrl = $avatar->url('thumbnail');

// Get the file path
$path = $avatar->getPath();

// Check if a file exists
if ($avatar->exists('thumbnail')) {
    // ...
}

// Check if a file is private
if ($avatar->is_private) {
    // Handle private file access
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

Lightpack supports both public and private file uploads:

### Public Uploads

Public uploads are stored in `uploads/public/` and are directly accessible via URL. Use these for:

- Images displayed on your website
- Public documents
- Any files that don't require access control

```php
// Public uploads (default)
$model->attach('image');
$model->attachMultiple('photos');
$model->attachFromUrl('https://example.com/image.jpg');
```

### Private Uploads

Private uploads are stored in `uploads/private/` and require access control. They are not directly accessible via URL. Use these for:

- Confidential documents
- User-specific files that require authentication
- Any files that should not be publicly accessible

```php
// Private uploads (using the 'private' flag)
$model->attach('document', ['private' => true]);
$model->attachMultiple('files', ['private' => true]);
$model->attachFromUrl('https://example.com/confidential.pdf', ['private' => true]);
```

### Serving Private Files

To serve private files, you need to create a controller that implements access control:

```php
class FileController
{
    public function serve(int $id)
    {
        // Get the upload
        $upload = (new UploadModel())->find($id);
        
        if (!$upload || !$upload->exists()) {
            return response()->setStatus(404)->send('File not found');
        }
        
        // Check if the current user has permission to access this file
        if ($upload->is_private && !$this->userCanAccessFile($upload)) {
            return response()->setStatus(403)->send('Unauthorized');
        }
        
        // Get the storage service
        $storage = app('storage');
        
        // Get the file contents
        $contents = $storage->read($upload->getPath());
        
        // Create a response with the file contents
        $response = response();
        $response->setHeader('Content-Type', $upload->mime_type);
        $response->setHeader('Content-Disposition', 'inline; filename="' . $upload->file_name . '"');
        
        return $response->send($contents);
    }
    
    protected function userCanAccessFile(UploadModel $upload): bool
    {
        // Implement your access control logic here
        return true; // Replace with actual logic
    }
}
```

Then set up a route to this controller:

```php
$router->get('/files/{id}', 'FileController@serve');
```

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

## Cloud Storage Integration

Lightpack's upload system works seamlessly with cloud storage providers like Amazon S3 and CloudFront.

### Amazon S3 Storage

When configured to use S3, Lightpack will:
- Store public files in the `uploads/public/` prefix
- Store private files in the `uploads/private/` prefix
- Generate appropriate URLs for each type

Configuration example:

```php
// config/storage.php
return [
    'default' => 's3',
    'drivers' => [
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'base_path' => env('AWS_BASE_PATH', ''),
        ],
    ],
];
```

### CloudFront Integration

For optimal performance and SEO, Lightpack supports CloudFront integration:

- Public files (`uploads/public/*`) are served via permanent CloudFront URLs
- Private files (`uploads/private/*`) can be served via signed CloudFront URLs

Configuration example:

```php
// config/storage.php
return [
    // ... S3 configuration ...
    
    'cloudfront' => [
        'domain' => env('CLOUDFRONT_DOMAIN'),
        'key_pair_id' => env('CLOUDFRONT_KEY_PAIR_ID'),
        'private_key' => env('CLOUDFRONT_PRIVATE_KEY_PATH'),
    ],
];
```

#### CloudFront Best Practices

1. **For SEO-friendly URLs**:
   - Configure CloudFront to serve your `uploads/public/*` files
   - Set appropriate cache behaviors for different file types
   - Use permanent URLs for public content

2. **For Private Content**:
   - Configure CloudFront with Origin Access Identity (OAI) to restrict direct S3 access
   - Use CloudFront signed URLs with appropriate expiration times
   - Set up path patterns to restrict access to `uploads/private/*`

3. **For Video Content**:
   - Use CloudFront for global distribution
   - Configure appropriate cache behaviors for video files
   - Consider using signed URLs with short expiration for premium content

### Serving Private Files with CloudFront

For private files, you can create a controller that generates signed URLs:

```php
class PrivateFileController
{
    public function serve(int $id)
    {
        // Get the upload
        $upload = (new UploadModel())->find($id);
        
        if (!$upload || !$upload->exists() || !$upload->is_private) {
            return response()->setStatus(404)->send('File not found');
        }
        
        // Check if the current user has permission to access this file
        if (!$this->userCanAccessFile($upload)) {
            return response()->setStatus(403)->send('Unauthorized');
        }
        
        // Get the storage service
        $storage = app('storage');
        
        // Generate a signed URL (works with both S3 and CloudFront)
        $signedUrl = $storage->url($upload->getPath(), 3600); // 1 hour expiration
        
        // Redirect to the signed URL
        return response()->redirect($signedUrl);
    }
    
    protected function userCanAccessFile(UploadModel $upload): bool
    {
        // Implement your access control logic here
        return true; // Replace with actual logic
    }
}
```

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

## Best Practices

### Security

1. **Always use private uploads for sensitive content**
   ```php
   $user->attach('tax_document', ['private' => true]);
   ```

2. **Implement proper access control in your controllers**
   ```php
   if (!$this->userCanAccessFile($upload)) {
       return response()->setStatus(403);
   }
   ```

3. **Use short expiration times for signed URLs**
   ```php
   // 15 minutes is often sufficient for most use cases
   $signedUrl = $storage->url($upload->getPath(), 900);
   ```

### Performance

1. **Use CloudFront for global content delivery**
   - Configure your storage to use CloudFront for improved load times

2. **Implement appropriate caching headers**
   ```php
   $response->setHeader('Cache-Control', 'public, max-age=31536000');
   ```

3. **Use appropriate image transformations**
   ```php
   // Don't serve original images directly to users
   $user->attach('product_image', [
       'transformations' => [
           'thumbnail' => ['resize' => [200, 200]],
           'medium' => ['resize' => [600, 600]],
           'large' => ['resize' => [1200, 1200]],
       ],
   ]);
   ```

### SEO

1. **Use public uploads for content that should be indexed**
   ```php
   $product->attach('featured_image');
   ```

2. **Configure CloudFront for permanent, SEO-friendly URLs**
   - This avoids temporary URLs that search engines won't index

3. **Use descriptive filenames**
   ```php
   $product->attachFromUrl($imageUrl, [
       'filename' => 'blue-denim-jeans-product-front-view.jpg',
   ]);
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
