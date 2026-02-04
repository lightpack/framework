<?php

namespace Lightpack\Http;

class Response
{
    /**
     * Standard HTTP status codes and their messages
     */
    public const STATUS_MESSAGES = [
        // 2xx Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        
        // 3xx Redirection
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        
        // 4xx Client Errors
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        422 => 'Unprocessable Entity',
        
        // 5xx Server Errors
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ];

    /**
     * Common security headers for better protection
     */
    private const SECURITY_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];

    /**
     * Cache directives and their descriptions
     */
    private const CACHE_DIRECTIVES = [
        'public' => 'Response can be cached by any cache',
        'private' => 'Response is for a single user only',
        'no-cache' => 'Must revalidate with server before using cached copy',
        'no-store' => 'Don\'t cache anything about the request/response',
        'must-revalidate' => 'Must revalidate stale cache entries with server',
        'max-age' => 'Maximum time in seconds to cache the response',
        'immutable' => 'Response will not change during cache lifetime',
    ];

    /**
     * Represents HTTP Content-Type
     */
    protected string $type = 'text/html';

    /**
     * Represents HTTTP response body
     */
    protected string $body = '';

    /**
     * Represents HTTP response status code
     */
    protected int $status = 200;

    /**
     * Represents HTTP response status message
     */
    protected string $message = 'OK';

    /**
     * Represents HTTP response headers.
     */
    protected array $headers = [];

    /**
     * Represents HTTP response redirect url.
     */
    protected string $redirectUrl = '';

    /**
     * Represents HTTP response stream callback.
     */
    protected $streamCallback;

    /**
     * Test mode flag to prevent exit() during tests
     */
    protected $testMode = false;

    /**
     * Return HTTP response status code.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Return HTTP response content type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Return HTTP response status message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Return HTTP response headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Return HTTP response header by name.
     */
    public function getHeader(string $name): string
    {
        return $this->headers[$name] ?? '';
    }

    /**
     * Return HTTP response body.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * This method sets the HTTP response status code.
     * If a standard HTTP message exists for the status code, it will be set automatically.
     * You can override this with setMessage() if needed.
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;
        
        // Set standard message if available
        if (isset(self::STATUS_MESSAGES[$status])) {
            $this->message = self::STATUS_MESSAGES[$status];
        }
        
        return $this;
    }

    /**
     * This method sets a response header.
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * This method sets multiple response headers.
     *
     * @param  array  $headers  An array of $name => $value header pairs.
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * This method sets the HTTP response message supplied by the client.
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * This method sets the HTTP response content type.
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * This method sets the HTTP response content.
     */
    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * This method sets the HTTP response redirect url.
     */
    public function setRedirectUrl(string $url): self
    {
        $this->redirectUrl = $url;
        return $this;
    }

    /**
     * This method returns the HTTP response redirect url.
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * This method sets the HTTP response content as JSON.
     * 
     * @param  mixed  $data The data to be encoded as JSON.
     */
    public function json($data): self
    {
        $json = json_encode($data);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed encoding JSON content: ' . json_last_error_msg());
        }

        $this->setType('application/json');
        $this->setBody($json);

        return $this;
    }

    /**
     * This method sets the HTTP response content as JSON.
     * 
     * @param  string  $data    XML formatted string.
     */
    public function xml(string $data): self
    {
        $this->setType('text/xml');
        $this->setBody($data);
        return $this;
    }

    /**
     * This method sets the HTTP response content as plain text.
     * 
     * @param  string  $data    Text content.
     */
    public function text(string $data): self
    {
        $this->setType('text/plain');
        $this->setBody($data);
        return $this;
    }

    /**
     * This method sends a download response to the client.
     *
     * @param string $path  The path of file to download.
     * @param string $name  Custom name for downloaded file.
     * @param array $headers  Additional headers for download response.
     */
    public function download(string $path, ?string $name = null, array $headers = []): self
    {
        $name = $name ?? basename($path);

        $headers = array_merge([
            'Content-Type'              => MimeTypes::getMime($path),
            'Content-Disposition'       => 'attachment; filename="' . $name . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Expires'                   => 0,
            'Cache-Control'             => 'private',
            'Pragma'                    => 'private',
            'Content-Length'            => filesize($path),
        ], $headers);

        $this->setBody(file_get_contents($path));
        $this->setHeaders($headers);

        return $this;
    }

    /**
     * This method streams a file download to the client in chunks, 
     * which is memory-efficient for large files.
     *
     * @param string $path  The path of file to download.
     * @param string $name  Custom name for downloaded file.
     * @param array $headers  Additional headers for download response.
     * @param int $chunkSize  Size of each chunk in bytes (default: 1MB)
     */
    public function downloadStream(string $path, ?string $name = null, array $headers = [], int $chunkSize = 1048576): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        
        $name = $name ?? basename($path);

        $headers = array_merge([
            'Content-Type'              => MimeTypes::getMime($path),
            'Content-Disposition'       => 'attachment; filename="' . $name . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Expires'                   => 0,
            'Cache-Control'             => 'private',
            'Pragma'                    => 'private',
            'Content-Length'            => filesize($path),
        ], $headers);

        $this->setHeaders($headers);
        
        // Use a streaming callback instead of loading the entire file
        $this->stream(function() use ($path, $chunkSize) {
            $handle = fopen($path, 'rb');
            
            // Disable output buffering to prevent memory build-up
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Send the file in chunks to keep memory usage low
            while (!feof($handle)) {
                echo fread($handle, $chunkSize);
                flush();
                
                // Allow the script to be terminated if the client disconnects
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
            
            fclose($handle);
        });
        
        return $this;
    }

    /**
     * This method display a file directly in the browser instead of downloading.
     *
     * @param string $path  The path of file to download.
     * @param string $name  Custom name for downloaded file.
     * @param array $headers  Additional headers for download response.
     */
    public function file(string $path, ?string $name = null, array $headers = []): self
    {
        $name = $name ?? basename($path);

        $headers = array_merge([
            'Content-Disposition' => 'inline; filename=' . $name
        ], $headers);

        return $this->download($path, $name, $headers);
    }

    /**
     * This method streams a file to be displayed directly in the browser,
     * which is memory-efficient for large files.
     *
     * @param string $path  The path of file to display.
     * @param string $name  Custom name for the file.
     * @param array $headers  Additional headers for the response.
     * @param int $chunkSize  Size of each chunk in bytes (default: 1MB)
     */
    public function fileStream(string $path, ?string $name = null, array $headers = [], int $chunkSize = 1048576): self
    {
        $name = $name ?? basename($path);

        $headers = array_merge([
            'Content-Disposition' => 'inline; filename=' . $name
        ], $headers);

        return $this->downloadStream($path, $name, $headers, $chunkSize);
    }

    /**
     * This method sets the HTTP response content as HTML.
     */
    public function view(string $file, array $data = []): self
    {
        $template = app('template')->setData($data)->include($file);

        $this->setBody($template);
        return $this;
    }

    /**
     * Enable test mode to prevent exit() during tests
     */
    public function setTestMode(bool $enabled = true): self
    {
        $this->testMode = $enabled;
        return $this;
    }

    /**
     * This method sends the response to the client.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            $this->sendHeaders();
        }

        if (!$this->redirectUrl) {
            $this->sendContent();
        }

        if (!$this->testMode) {
            exit;
        }
    }

    /**
     * Check if the header is set in the response.
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * This method sends all HTTP headers.
     */
    private function sendHeaders(): void
    {
        header(sprintf("HTTP/1.1 %s %s", $this->status, $this->message));
        header(sprintf("Content-Type: %s; charset=UTF-8", $this->type));

        foreach ($this->headers as $name => $value) {
            header(sprintf("%s: %s", $name, $value), true, $this->status);
        }
    }

    /**
     * This method outputs the HTTP response body.
     */
    private function sendContent(): void
    {
        if (isset($this->streamCallback)) {
            // For streaming responses
            call_user_func($this->streamCallback);
        } else {
            // For regular responses
            echo $this->body;
        }
    }

    /**
     * Stream content using a callback function.
     * 
     * @param callable $callback Function that writes to output
     */
    public function stream(callable $callback): self
    {
        $this->streamCallback = $callback;
        return $this;
    }

    /**
     * Get the stream callback function.
     * 
     * @return callable|null The stream callback function
     */
    public function getStreamCallback()
    {
        return $this->streamCallback;
    }

    /**
     * Apply recommended security headers to the response
     */
    public function secure(array $customHeaders = []): self
    {
        // Merge default security headers with any custom ones
        $headers = array_merge(self::SECURITY_HEADERS, $customHeaders);
        
        // Set HSTS only on HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }
        
        return $this->setHeaders($headers);
    }

    /**
     * Configure response for Server-Sent Events (SSE) streaming.
     * 
     * @param callable $callback Callback that receives stream object with push() method
     * @return self
     */
    public function sse(callable $callback): self
    {
        // Set SSE headers
        $this->setHeader('Content-Type', 'text/event-stream')
             ->setHeader('Cache-Control', 'no-cache')
             ->setHeader('Connection', 'keep-alive')
             ->setHeader('X-Accel-Buffering', 'no');
        
        // Set up streaming
        $this->stream(function() use ($callback) {
            $callback($this->createEventStream());
        });
        
        return $this;
    }

    /**
     * Create an anonymous class for SSE event streaming.
     * 
     * @return object Object with push() method for sending SSE events
     */
    protected function createEventStream(): object
    {
        return new class {
            public function push(string $event, array $data = []): void
            {
                $payload = array_merge(['event' => $event], $data);
                echo "data: " . json_encode($payload) . "\n\n";
                
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        };
    }

    /**
     * Enable caching for the response with given options
     *
     * @param int $maxAge Maximum age in seconds (e.g., 3600 for 1 hour)
     * @param array $options Additional cache options:
     *                      - public: boolean, can be cached by intermediate caches
     *                      - immutable: boolean, content won't change during maxAge
     */
    public function cache(int $maxAge, array $options = []): self
    {
        $directives = ['max-age=' . $maxAge];
        
        // Public by default
        if (!isset($options['public']) || $options['public']) {
            $directives[] = 'public';
        } else {
            $directives[] = 'private';
        }
        
        // Mark as immutable if specified
        if (isset($options['immutable']) && $options['immutable']) {
            $directives[] = 'immutable';
        }
        
        // Set cache headers
        return $this->setHeaders([
            'Cache-Control' => implode(', ', $directives),
            'Expires' => gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT',
            'Pragma' => 'cache',
        ]);
    }

    /**
     * Disable caching for the response
     */
    public function noCache(): self
    {
        return $this->setHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT', // Date in the past
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Set Last-Modified header
     * 
     * @param int|string|\DateTimeInterface $time Timestamp, date string, or DateTime
     */
    public function setLastModified($time): self
    {
        if ($time instanceof \DateTimeInterface) {
            $time = $time->getTimestamp();
        } elseif (is_string($time)) {
            $time = strtotime($time);
        }

        return $this->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $time) . ' GMT');
    }

    /**
     * Stream response as CSV.
     *
     * @param callable $callback Function that writes CSV data
     * @param string $filename Name of the CSV file to download
     */
    public function streamCsv(callable $callback, string $filename = 'export.csv'): self
    {
        return $this
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->stream($callback);
    }
}
