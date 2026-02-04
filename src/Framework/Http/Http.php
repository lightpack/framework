<?php

namespace Lightpack\Http;

/**
 * A lightweight HTTP client for making API requests.
 * 
 * Features:
 * - Fluent interface for HTTP methods (get, post, put, patch, delete)
 * - Support for JSON and form-urlencoded data
 * - Custom headers and bearer token authentication
 * - File uploads
 * - Timeout and SSL configuration
 * - Response status helpers (ok, failed, clientError, serverError)
 */
class Http
{
    protected array $headers = [];
    protected array $options = [];
    protected ?string $error = null;
    protected mixed $response = null;
    protected int $statusCode = 0;
    protected array $files = [];
    protected array $responseHeaders = [];

    public function __construct()
    {
        $this->options[CURLOPT_TIMEOUT] = 30;
        $this->options[CURLOPT_FOLLOWLOCATION] = true;
    }

    public function get(string $url, array $query = []): self
    {
        return $this->request('GET', $url, $query);
    }

    public function post(string $url, array $data = []): self
    {
        return $this->request('POST', $url, $data);
    }

    public function put(string $url, array $data = []): self
    {
        return $this->request('PUT', $url, $data);
    }

    public function patch(string $url, array $data = []): self
    {
        return $this->request('PATCH', $url, $data);
    }

    public function delete(string $url, array $data = []): self
    {
        return $this->request('DELETE', $url, $data);
    }

    public function headers(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function files(array $files): self
    {
        if (empty($files)) {
            return $this;
        }

        foreach ($files as $key => $file) {
            if (is_string($file) && file_exists($file)) {
                $this->files[$key] = new \CURLFile($file);
            } elseif ($file instanceof \CURLFile) {
                $this->files[$key] = $file;
            }
        }

        return $this;
    }

    /**
     * Get the HTTP status code of the response.
     * 
     * Returns:
     * - 2xx for successful requests
     * - 4xx for client errors (like 404 Not Found)
     * - 5xx for server errors
     * - 0 if the request failed to connect
     */
    public function status(): int
    {
        return $this->statusCode;
    }

    /**
     * Get connection/transport level error message if any.
     * 
     * Examples of transport errors:
     * - Could not resolve host (DNS failure)
     * - Connection refused
     * - Connection timed out
     * - SSL certificate verification failed
     * - No route to host
     * 
     * Note: These are low-level errors that occur before getting an HTTP response.
     * For HTTP-level errors (404, 500 etc), check the status() code instead.
     * 
     * @return string|null Error message or null if connection was successful
     */
    public function error(): ?string
    {
        return $this->error;
    }

    /**
     * Get the response data as array.
     * Note: Assumes JSON response.
     */
    public function json(): array
    {
        return json_decode($this->response, true) ?? [];
    }

    /**
     * Send request body as form-urlencoded data.
     * Use this when submitting traditional HTML forms.
     */
    public function form(): self
    {
        $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        return $this;
    }

    /**
     * Get the raw response body.
     * Returns empty string if request failed or no response.
     */
    public function body(): string
    {
        return $this->response ?? '';
    }

    /**
     * Check if status code is in the 2xx range (success).
     */
    public function ok(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if status code is in the 3xx range (redirect).
     */
    public function redirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if status code is in the 4xx range (client error).
     */
    public function clientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if status code is in the 5xx range (server error).
     */
    public function serverError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Check if the request failed.
     * 
     * A request can fail in two ways:
     * 1. Transport failure: Connection/DNS/SSL errors (status = 0)
     * 2. HTTP failure: Server returned 4xx or 5xx status
     */
    public function failed(): bool 
    {
        // Transport error = no HTTP response at all
        if (!empty($this->error)) {
            return true;
        }

        // HTTP error = got response but it's 4xx/5xx
        return $this->statusCode >= 400;
    }

    private function request(string $method, string $url, array $data = []): self
    {
        $ch = curl_init();
        
        // Build URL with query parameters for GET requests
        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, true);

        // Set headers
        $headers = [];
        if ($method !== 'GET') {
            // Default to JSON unless form-encoded is requested
            if (!isset($this->headers['Content-Type'])) {
                $this->headers['Content-Type'] = 'application/json';
            }
        }

        foreach ($this->headers as $key => $value) {
            $headers[] = "$key: $value";
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Set request body for non-GET requests
        if ($method !== 'GET' && !empty($data)) {
            $body = $this->headers['Content-Type'] === 'application/x-www-form-urlencoded'
                ? http_build_query($data)
                : json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // Set files for upload
        if (!empty($this->files)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->files);
        }

        // Set custom options
        foreach ($this->options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        // Execute request
        $response = curl_exec($ch);
        $this->error = curl_error($ch);
        $this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        curl_close($ch);

        // Separate headers and body
        $headerString = substr($response, 0, $headerSize);
        $this->response = substr($response, $headerSize);
        $this->responseHeaders = $this->parseHeaders($headerString);

        $this->resetState();

        return $this;
    }

    /**
     * Set request timeout in seconds.
     */
    public function timeout(int $seconds): self
    {
        $this->options[CURLOPT_TIMEOUT] = $seconds;
        $this->options[CURLOPT_CONNECTTIMEOUT] = $seconds;
        return $this;
    }

    /**
     * Disable SSL verification (use with caution).
     */
    public function insecure(): self
    {
        $this->options[CURLOPT_SSL_VERIFYPEER] = false;
        $this->options[CURLOPT_SSL_VERIFYHOST] = 0;
        return $this;
    }

    /**
     * Set authentication bearer token.
     * 
     * Example:
     *   $client->token('xyz')->get('https://api.example.com');
     */
    public function token(string $token): self
    {
        return $this->headers(['Authorization' => 'Bearer ' . $token]);
    }

    /**
     * Make a streaming HTTP request with chunk callback.
     * 
     * Unlike regular HTTP methods, this streams the response in real-time
     * by calling the provided callback for each chunk of data received.
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $url The endpoint URL
     * @param array|null $data Request body data (will be JSON encoded for POST/PUT/PATCH)
     * @param callable $onChunk Callback function: fn(string $chunk) => void
     * @return self
     * @throws \Exception If the request fails
     */
    public function stream(string $method, string $url, ?array $data, callable $onChunk): self
    {
        $ch = curl_init();
        $method = strtoupper($method);
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Don't buffer response
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        // Prepare headers
        $headers = [];
        if ($data !== null && !isset($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = 'application/json';
        }
        
        foreach ($this->headers as $key => $value) {
            $headers[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set request body for methods that support it
        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $body = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        
        // Set custom options (timeout, etc.)
        foreach ($this->options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }
        
        // Set write callback for streaming
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use ($onChunk) {
            $onChunk($chunk);
            return strlen($chunk); // Must return bytes processed
        });
        
        // Execute streaming request
        $success = curl_exec($ch);
        $this->error = curl_error($ch);
        $this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        // Check for errors
        if (!$success || $this->statusCode >= 400) {
            $errorMsg = $this->error ?: 'HTTP error ' . $this->statusCode;
            throw new \Exception('Streaming request failed: ' . $errorMsg);
        }
        
        $this->resetState();
        
        return $this;
    }

    /**
     * Set custom cURL options for the request.
     * 
     * @param array $options Array of CURLOPT_* options
     */
    public function options(array $options): self
    {
        foreach ($options as $option => $value) {
            $this->options[$option] = $value;
        }
        return $this;
    }

    /**
     * Download a file from URL and save it to disk.
     * 
     * @param string $url URL to download from
     * @param string $savePath Path where to save the file
     * @return bool True if download was successful
     */
    public function download(string $url, string $savePath): bool
    {
        $fp = fopen($savePath, 'w+');
        
        if ($fp === false) {
            throw new \RuntimeException(
                sprintf('Failed to open file for writing: %s', $savePath)
            );
        }
        
        $this->options[CURLOPT_URL] = $url;
        $this->options[CURLOPT_FILE] = $fp;
        $this->options[CURLOPT_FOLLOWLOCATION] = true;
        
        $ch = curl_init();
        curl_setopt_array($ch, $this->options);
        
        $success = curl_exec($ch);
        $this->error = curl_error($ch);
        $this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        fclose($fp);
        
        if (!$success) {
            @unlink($savePath);
        }
        
        $this->resetState();
        
        return $success;
    }

    protected function resetState(): void
    {
        $this->headers = [];
        $this->options = [];
        $this->files = [];
    }

    /**
     * Get all response headers.
     * 
     * @return array Associative array of header name => value
     */
    public function responseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * Get a specific response header value.
     * Header names are case-insensitive.
     * 
     * @param string $name Header name
     * @return string|null Header value or null if not found
     */
    public function responseHeader(string $name): ?string
    {
        $name = strtolower($name);
        return $this->responseHeaders[$name] ?? null;
    }

    /**
     * Parse raw header string into associative array.
     * 
     * @param string $headerString Raw header string from cURL
     * @return array Parsed headers (lowercase keys)
     */
    protected function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        return $headers;
    }
}
