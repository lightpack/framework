# HTTP Response

The Response class in Lightpack provides a clean and intuitive API for handling HTTP responses. It supports various response types including JSON, XML, file downloads, and streaming responses.

## Basic Usage

```php
// Basic text response
response()->text('Hello World');

// JSON response
response()->json(['message' => 'Success']);

// XML response
response()->xml('<root><message>Success</message></root>');
```

## Headers and Status

```php
response()
    ->setStatus(201)
    ->setHeader('X-Custom-Header', 'value')
    ->json(['status' => 'created']);
```

## File Downloads

```php
// Download a file
response()->download('/path/to/file.pdf', 'document.pdf');
```

## Streaming Responses

The `stream()` method allows you to send large amounts of data to the client without loading everything into memory at once. This is perfect for:
- Large file downloads
- CSV exports
- Real-time data feeds
- Long-running processes

### Basic Streaming

```php
return response()
    ->setHeader('Content-Type', 'text/plain')
    ->stream(function() {
        echo "Line 1\n";
        echo "Line 2\n";
        // Output as much as you need
    });
```

### CSV Export Example

Here's a real-world example of streaming a large CSV export:

```php
public function exportUsers()
{
    if (!User::query()->exists()) {
        return redirect()->back()->with('error', 'No users to export');
    }

    return response()
        ->setHeader('Content-Type', 'text/csv')
        ->setHeader('Content-Disposition', 'attachment; filename="users.csv"')
        ->stream(function() {
            // Write CSV headers
            fputcsv(STDOUT, ['Name', 'Email', 'Created At']);

            // Stream users in chunks to keep memory usage low
            User::query()->chunk(1000, function($users) {
                foreach ($users as $user) {
                    fputcsv(STDOUT, [
                        $user->name,
                        $user->email,
                        $user->created_at
                    ]);
                }
            });
        });
}
```

### Streaming with Progress Updates

You can even stream with progress updates:

```php
return response()
    ->setHeader('Content-Type', 'text/event-stream')
    ->setHeader('Cache-Control', 'no-cache')
    ->stream(function() {
        $total = 100;
        
        for ($i = 1; $i <= $total; $i++) {
            echo "data: " . json_encode([
                'progress' => ($i / $total) * 100
            ]) . "\n\n";
            
            // Some processing...
            usleep(100000); // 0.1 second delay
            
            // Flush output buffer
            ob_flush();
            flush();
        }
    });
```

### Memory Efficiency

The streaming response is particularly useful when dealing with large datasets. Instead of loading everything into memory, it:
1. Processes data in chunks
2. Outputs each chunk immediately
3. Frees memory after each chunk
4. Keeps your application responsive

### Best Practices

1. **Set Proper Headers**
   ```php
   response()
       ->setHeader('Content-Type', 'appropriate/type')
       ->stream(/* ... */);
   ```

2. **Use Chunking for Large Datasets**
   ```php
   Model::query()->chunk(1000, function($items) {
       foreach ($items as $item) {
           // Process and output each item
       }
   });
   ```

3. **Flush Output Buffer** for real-time updates
   ```php
   ob_flush();
   flush();
   ```

4. **Error Handling**
   ```php
   return response()
       ->stream(function() {
           try {
               // Your streaming logic
           } catch (Exception $e) {
               // Log error
               echo "Error: " . $e->getMessage();
           }
       });
   ```

## Security Headers

```php
// Add security headers
response()->secure()->json($data);
```

## Caching

```php
// Enable caching
response()->cache(3600)->json($data);

// Disable caching
response()->noCache()->json($data);
```
