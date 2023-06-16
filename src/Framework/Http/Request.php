<?php

namespace Lightpack\Http;

use Lightpack\Exceptions\InvalidHttpMethodException;
use Lightpack\Exceptions\InvalidUrlSignatureException;
use Lightpack\Routing\Route;
use Lightpack\Utils\Url;

class Request
{
    private Files $files;
    private Header $headers;
    private string $basepath;
    private string $method;
    private ?array $jsonBody = null;
    private ?string $rawBody = null;
    private ?string $parsedBody = null;
    private bool $isSpoofed = false;
    private Route $route;
    private static array $verbs = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD',
        'OPTIONS',
        'CONNECT',
        'TRACE',
        'PURGE',
    ];

    public function __construct(string $basepath = null)
    {
        $this->basepath = $basepath ?? dirname($_SERVER['SCRIPT_NAME']);
        $this->files = new Files($_FILES ?? []);
        $this->headers = new Header;
        $this->setMethod();
    }

    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'];
    }

    public function query(string $key = null, $default = null)
    {
        return $this->queryData($key, $default);
    }

    public function basepath(): string
    {
        return $this->basepath;
    }

    public function setBasePath(string $path): self
    {
        $this->basepath = $path;

        return $this;
    }

    public function fullpath(): string
    {
        $path = explode('?', $_SERVER['REQUEST_URI'])[0];

        return '/' . trim($path, '/');
    }

    public function path(): string
    {
        $path = substr(
            $this->fullpath(),
            strlen($this->basepath)
        );

        return '/' . trim($path, '/');
    }

    public function segments(int $index = null)
    {
        $segments = explode('/', trim($this->path(), '/'));

        if ($index === null) {
            return $segments;
        }

        return $segments[$index] ?? null;
    }

    public function url(): string
    {
        return $this->scheme() . '://' . $this->host() . $this->fullpath();
    }

    public function fullUrl(): string
    {
        return $this->scheme() . '://' . $this->host() . $this->uri();
    }

    public function method(): string
    {
        return $this->method;
    }

    public function getRawBody(): string
    {
        if (null === $this->rawBody) {
            $this->parseBody();
        }

        return $this->rawBody ?? '';
    }

    public function getParsedBody(?string $key = null, $default = null): string
    {
        if (empty($this->parsedBody)) {
            parse_str($this->getRawBody(), $this->parsedBody);
        }

        if (null === $key) {
            return $this->parsedBody;
        }

        return $this->parsedBody[$key] ?? $default;
    }

    /**
     * Return HTTP request input data from get, post, put, patch, delete requests.
     */
    public function input(?string $key = null, $default = null): mixed
    {
        if ($this->isJson()) {
            return $this->json($key, $default);
        }

        if ($this->isSpoofed()) {
            return $this->postData($key, $default);
        }

        match ($this->method) {
            'GET' => $value = $this->queryData($key, $default),
            'POST' => $value = $this->postData($key, $default),
            'PUT', 'PATCH', 'DELETE' => $value = $this->getParsedBody($key, $default),
        };

        // Always fallback to $_GET
        return $value ?? $this->queryData($key, $default);
    }

    public function json(?string $key = null, $default = null): mixed
    {
        if (null === $this->jsonBody) {
            $this->parseJson();
        }

        if (null === $key) {
            return $this->jsonBody;
        }

        return $this->jsonBody[$key] ?? $default;
    }

    public function files()
    {
        return $this->files;
    }

    /**
     * Get the value of a file or files associated with a given key.
     * 
     * It returns null if the key is not found, an UploadedFile if the
     * key has a single file, or an array of UploadedFile objects if
     * the key has multiple files.
     *
     * @param string|null $key The key to retrieve the file(s) for. If null, returns all files.
     * @return null|UploadedFile|UploadedFile[] 
     */
    public function file(?string $key = null)
    {
        return $this->files->get($key);
    }

    public function hasFile(string $key)
    {
        return $this->files->has($key);
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isPut()
    {
        return $this->method === 'PUT';
    }

    public function isPatch()
    {
        return $this->method === 'PATCH';
    }

    public function isDelete()
    {
        return $this->method === 'DELETE';
    }

    public function isSpoofed(): bool
    {
        return $this->isSpoofed;
    }

    public function isAjax()
    {
        return ($_SERVER['X-Requested-With'] ?? null)  === 'XMLHttpRequest';
    }

    public function isJson()
    {
        return false !== stripos($this->format(), 'json');
    }

    public function isSecure()
    {
        return $this->scheme() === 'https';
    }

    public function scheme()
    {
        if (
            (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') ||
            (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == '443'))
        ) {
            return 'https';
        }

        return 'http';
    }

    public function host()
    {
        return $_SERVER['HTTP_HOST'] ?? getenv('HTTP_HOST');
    }

    public function protocol()
    {
        return $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
    }

    public function format()
    {
        return $_SERVER['CONTENT_TYPE'] ?? '';
    }

    public function verbs()
    {
        return self::$verbs;
    }

    public function header(string $key, string $default = null): ?string
    {
        return $this->headers->get($key, $default);
    }

    public function headers(): array
    {
        return $this->headers->all();
    }

    public function hasHeader(string $key): bool
    {
        return $this->headers->has($key);
    }

    /**
     * Get bearer token from Authorization header.
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');

        if (null === $header) {
            return null;
        }

        if (false === strpos($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }

    /**
     * Get refferer from referer header.
     *
     * @return string|null
     */
    public function referer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    public function setMethod(string $method = null): self
    {
        $method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $method = strtoupper($method);

        if ('POST' === $method) {
            // has it been spoofed?
            $method = strtoupper($_POST['_method'] ?? $method);
            $this->isSpoofed = isset($_POST['_method']);
        }

        if (!in_array($method, self::$verbs)) {
            throw new InvalidHttpMethodException('Invalid HTTP request method ' . $method);
        }

        $this->method = $method;

        return $this;
    }

    /** 
     * Set the resolved route instance for the current request.
     */
    public function setRoute(Route $route): self
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Get the resolved route instance for the current request.
     */
    public function route(): ?Route
    {
        return $this->route;
    }

    public function validateUrlSignature(array $ignoredParameters = [])
    {
        if ($this->hasInValidSignature($ignoredParameters)) {
            throw new InvalidUrlSignatureException;
        }
    }

    public function hasValidSignature(array $ignoredParameters = []): bool
    {
        return (new Url)->verify($this->fullUrl(), $ignoredParameters);
    }

    public function hasInValidSignature(array $ignoredParameters = []): bool
    {
        return !$this->hasValidSignature($ignoredParameters);
    }

    public function subdomain(): ?string
    {
        $host = $this->host();
        $parts = explode('.', $host);

        // Check if the host consists of at least two parts (subdomain.domain)
        if (count($parts) >= 2) {
            // Remove the last part (domain) and return the remaining subdomain
            array_pop($parts);
            return implode('.', $parts);
        }

        return null;
    }

    private function parseBody()
    {
        $rawBody = $_SERVER['X_LIGHTPACK_RAW_INPUT'] ?? file_get_contents('php://input');

        $this->rawBody = $rawBody ?: '';
    }

    private function parseJson()
    {
        $rawBody = $this->getRawBody();

        if (empty($rawBody)) {
            return $this->jsonBody = [];
        }

        $json = json_decode($rawBody, true);

        if ($json === null) {
            throw new \RuntimeException('Error decoding request body as JSON');
        }

        $this->jsonBody = $json;
    }

    private function queryData(string $key = null, $default = null)
    {
        if (null === $key) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    private function postData(string $key = null, $default = null)
    {
        if (null === $key) {
            return $_POST;
        }

        return $_POST[$key] ?? $default;
    }
}
