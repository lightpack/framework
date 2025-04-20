# File Storage in Lightpack

This document provides a comprehensive guide to Lightpack's file storage system, including its architecture, usage patterns, and implementation details.

## Table of Contents

1. [Introduction](#introduction)
2. [Storage Architecture](#storage-architecture)
3. [Basic Usage](#basic-usage)
4. [Public vs Private Storage](#public-vs-private-storage)
5. [Storage Drivers](#storage-drivers)
   - [Local Storage](#local-storage)
   - [S3 Storage](#s3-storage)
   - [S3-Compatible Services](#s3-compatible-services)
6. [URL Generation](#url-generation)
7. [Configuration](#configuration)
8. [Security Considerations](#security-considerations)
9. [Best Practices](#best-practices)
10. [Evolution of the Storage System](#evolution-of-the-storage-system)

## Introduction

File storage is a critical component of most web applications. Lightpack provides a simple, flexible, and secure file storage system that allows you to:

- Store uploaded files in various storage backends (local filesystem, Amazon S3, etc.)
- Control access to files with public and private storage options
- Generate URLs for accessing stored files
- Handle file uploads securely

The storage system follows Lightpack's philosophy of simple, practical solutions with explicit interfaces.

## Storage Architecture

Lightpack's storage system is built around the following key components:

1. **Storage Interface**: Defines the contract that all storage drivers must implement.
2. **Storage Drivers**: Concrete implementations for different storage backends (local filesystem, S3, etc.).
3. **UploadedFile Class**: Handles file uploads and provides a convenient API for storing files.
4. **URL Generation**: Methods for generating URLs to access stored files.

The system uses a driver-based architecture, allowing you to easily switch between different storage backends without changing your application code.

## Basic Usage

### Handling File Uploads

```php
// In your controller
public function store()
{
    $file = $this->request->file('avatar');
    
    if ($file->isValid()) {
        // Store the file and get its path
        $path = $file->store('uploads/users');
        
        // Save the path to the database
        $user->avatar = $path;
        $user->save();
    }
}
```

### Reading Files

```php
// Get the storage instance
$storage = Container::getInstance()->get('storage');

// Read file contents
$contents = $storage->read('uploads/users/avatar.jpg');

// Check if a file exists
if ($storage->exists('uploads/users/avatar.jpg')) {
    // Do something with the file
}
```

### Writing Files

```php
// Write contents to a file
$storage->write('logs/app.log', 'Log message');
```

### Deleting Files

```php
// Delete a file
$storage->delete('uploads/users/old-avatar.jpg');
```

### Copying and Moving Files

```php
// Copy a file
$storage->copy('uploads/users/avatar.jpg', 'backups/users/avatar.jpg');

// Move a file
$storage->move('uploads/temp/avatar.jpg', 'uploads/users/avatar.jpg');
```

## Public vs Private Storage

Lightpack distinguishes between public and private file storage:

### Public Files

- Stored in `uploads/public/` directory
- Directly accessible via URL
- Ideal for images, public downloads, etc.
- For S3, stored with `public-read` ACL

```php
// Store a public file and get its URL
$url = $file->storePublic('users/avatars', [
    'unique' => true,
]);

// URL for local storage: /uploads/users/avatars/filename.jpg
// URL for S3: https://bucket.s3.amazonaws.com/uploads/public/users/avatars/filename.jpg
```

### Private Files

- Stored in `uploads/private/` directory
- Not directly accessible via URL
- Requires access control through a controller
- Ideal for user documents, sensitive files, etc.
- For S3, stored with `private` ACL

```php
// Store a private file and get its path
$path = $file->storePrivate('users/documents', [
    'unique' => true,
]);

// Path: uploads/private/users/documents/filename.pdf

// Later, generate a URL to access the private file
$url = $storage->url($path);

// URL for local storage: /files/serve?path=uploads/private/users/documents/filename.pdf
// URL for S3: https://pre-signed-url.amazonaws.com/... (temporary URL)
```

## Storage Drivers

### Local Storage

The local storage driver stores files on the local filesystem. It's the default driver and is ideal for development and small applications.

Key features:
- Uses `move_uploaded_file()` for secure file uploads
- Supports directory creation and permission checks
- Generates URLs based on the public/private distinction

For public files, it assumes a symlink from `public/uploads` to `storage/uploads/public` (created with the `php lightpack link:storage` command).

### S3 Storage

The S3 storage driver stores files on Amazon S3. It's ideal for production applications and provides scalable, reliable storage.

Key features:
- Uses the AWS SDK for PHP
- Supports public and private ACLs
- Generates direct URLs for public files
- Generates pre-signed URLs for private files

### S3-Compatible Services

The S3 storage driver also works with S3-compatible services like:

- **DigitalOcean Spaces**
- **Backblaze B2**
- **MinIO**

To use these services, you just need to configure the S3 client with the appropriate endpoint and credentials:

```php
// For DigitalOcean Spaces
$client = new S3Client([
    'version' => 'latest',
    'region' => 'nyc3',
    'endpoint' => 'https://nyc3.digitaloceanspaces.com',
    'credentials' => [
        'key' => 'your-spaces-key',
        'secret' => 'your-spaces-secret',
    ],
    'use_path_style_endpoint' => true,
]);

$storage = new S3Storage($client, 'your-bucket', 'optional-prefix');
```

## URL Generation

Lightpack provides a consistent way to generate URLs for stored files, regardless of the storage driver:

```php
// Get a URL for a file
$url = $storage->url('uploads/public/users/avatar.jpg');

// Get a temporary URL for a private file (expires in 1 hour)
$url = $storage->url('uploads/private/users/document.pdf', 3600);
```

The URL generation behavior depends on the storage driver and the file path:

1. **Local Storage**:
   - Public files (`uploads/public/`): `/uploads/path/to/file.jpg`
   - Private files: `/files/serve?path=uploads/private/path/to/file.pdf`

2. **S3 Storage**:
   - Public files (`uploads/public/`): `https://bucket.s3.amazonaws.com/uploads/public/path/to/file.jpg`
   - Private files: Pre-signed URL with expiration

## Configuration

### Local Storage Setup

1. Create the necessary directories:
   ```bash
   mkdir -p storage/uploads/public
   mkdir -p storage/uploads/private
   ```

2. Create a symlink from `public/uploads` to `storage/uploads/public`:
   ```bash
   php lightpack link:storage
   ```

### S3 Storage Setup

1. Install the AWS SDK for PHP:
   ```bash
   composer require aws/aws-sdk-php
   ```

2. Configure the S3 client:
   ```php
   use Aws\S3\S3Client;
   use Lightpack\Storage\S3Storage;

   $client = new S3Client([
       'version' => 'latest',
       'region' => 'us-east-1',
       'credentials' => [
           'key' => 'your-aws-key',
           'secret' => 'your-aws-secret',
       ],
   ]);

   $storage = new S3Storage($client, 'your-bucket', 'optional-prefix');
   ```

3. Register the storage driver in your service container:
   ```php
   $container->bind('storage', function() {
       // Return the configured storage driver
       return $storage;
   });
   ```

## Security Considerations

### Local Storage

1. **Directory Traversal**: The storage system prevents directory traversal attacks by validating paths.
2. **Secure Uploads**: Uses `move_uploaded_file()` which verifies that the file was uploaded via HTTP POST.
3. **Access Control**: Private files are served through a controller that can implement access control.

### S3 Storage

1. **ACLs**: Public files use `public-read` ACL, private files use `private` ACL.
2. **Pre-signed URLs**: Private files are accessed via pre-signed URLs with expiration.
3. **Bucket Policy**: Ensure your S3 bucket has appropriate policies to prevent unauthorized access.

## Best Practices

1. **Use Public Storage Sparingly**: Only store files in public storage if they need to be directly accessible.
2. **Validate Uploads**: Always validate uploaded files before storing them.
3. **Use Unique Filenames**: Use the `unique` option to prevent filename collisions.
4. **Store Paths in Database**: Store file paths in your database, not full URLs.
5. **Clean Up Unused Files**: Implement a system to clean up unused files.

## Evolution of the Storage System

### Early Web Development Approach

In the early days of web development, file uploads were typically handled by storing files directly in the public web directory:

```php
move_uploaded_file($_FILES['avatar']['tmp_name'], 'public/uploads/avatar.jpg');
```

This approach had several drawbacks:
- Security issues with files being directly accessible
- No access control for sensitive files
- Difficult to switch to cloud storage
- No abstraction or organization

### Modern Storage Architecture

Modern frameworks, including Lightpack, have evolved to use a more sophisticated approach:

1. **Driver-Based Architecture**: Abstract storage operations behind a common interface.
2. **Public/Private Distinction**: Separate public and private files.
3. **Cloud Storage Support**: Seamless integration with cloud storage providers.
4. **URL Generation**: Consistent URL generation across storage drivers.
5. **Security Enhancements**: Better handling of file uploads and access control.

Lightpack's storage system embodies these modern principles while maintaining the framework's philosophy of simplicity and explicitness.

### Future Directions

The storage system could evolve in several directions:

1. **Additional Drivers**: Support for more storage backends (Google Cloud Storage, Azure Blob Storage, etc.).
2. **Streaming Uploads**: Support for streaming large file uploads without loading them into memory.
3. **Image Processing**: Integration with image processing libraries for resizing, cropping, etc.
4. **File Validation**: More comprehensive file validation and sanitization.
5. **Metadata Support**: Better support for file metadata and content types.

## Conclusion

Lightpack's storage system provides a simple, flexible, and secure way to handle file storage in your applications. By abstracting storage operations behind a common interface, it allows you to easily switch between different storage backends without changing your application code.

Whether you're storing user avatars, document uploads, or application assets, the storage system gives you the tools you need to do it securely and efficiently.
