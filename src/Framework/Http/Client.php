<?php

namespace Lightpack\Http;

class Client
{
    protected array $headers = [];
    protected array $options = [];
    protected ?string $error = null;
    protected mixed $response = null;
    protected int $statusCode = 0;
    protected array $files = [];

    public function __construct()
    {
        $this->options[CURLOPT_TIMEOUT] = 5;
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

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getJson()
    {
        return json_decode($this->response, true);
    }

    public function getText(): string
    {
        return $this->response;
    }

    public function failed(): bool 
    {
        return !empty($this->error);
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

        // Set headers
        $headers = [];
        if ($method !== 'GET') {
            $this->headers['Content-Type'] = 'application/json';
        }

        foreach ($this->headers as $key => $value) {
            $headers[] = "$key: $value";
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Set request body for non-GET requests
        if ($method !== 'GET' && !empty($data)) {
            $body = json_encode($data);
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
        $this->response = curl_exec($ch);
        $this->error = curl_error($ch);
        $this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // Reset state for next request
        $this->headers = [];
        $this->options = [];
        $this->files = [];

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
}
