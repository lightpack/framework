<?php

namespace Lightpack\Http;

class Client
{
    private array $headers = [];
    private array $options = [];
    private ?string $error = null;
    private mixed $response = null;
    private int $statusCode = 0;
    private array $files = [];

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

    public function delete(string $url, array $data = []): self
    {
        return $this->request('DELETE', $url, $data);
    }

    public function headers(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function json(): self
    {
        return $this->headers(['Content-Type' => 'application/json']);
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
        foreach ($this->headers as $key => $value) {
            $headers[] = "$key: $value";
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Set request body for non-GET requests
        if ($method !== 'GET' && !empty($data)) {
            $body = isset($this->headers['Content-Type']) && $this->headers['Content-Type'] === 'application/json'
                ? json_encode($data)
                : http_build_query($data);
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // Set files for upload
        if (!empty($this->files)) {
            $body = [];
            foreach ($this->files as $key => $file) {
                $body[$key] = $file;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // Set additional curl options
        foreach ($this->options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $this->response = curl_exec($ch);
        $this->error = curl_error($ch);
        $this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $this;
    }

    /**
     * Set CURL options.
     * 
     * Example:
     *   $client->options([
     *       CURLOPT_TIMEOUT => 30,
     *       CURLOPT_SSL_VERIFYPEER => false
     *   ]);
     */
    public function options(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Set request timeout in seconds.
     */
    public function timeout(int $seconds): self
    {
        return $this->options([CURLOPT_TIMEOUT => $seconds]);
    }

    /**
     * Disable SSL verification (use with caution).
     */
    public function insecure(): self
    {
        return $this->options([
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
    }
}
