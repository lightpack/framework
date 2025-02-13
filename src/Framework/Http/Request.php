<?php

namespace Lightpack\Http;

use Lightpack\Exceptions\InvalidHttpMethodException;
use Lightpack\Exceptions\InvalidUrlSignatureException;
use Lightpack\Routing\Route;
use Lightpack\Utils\Url;
use Lightpack\Utils\Arr;

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
    private Arr $arr;

    public function __construct(string $basepath = null)
    {
        $this->basepath = $basepath ?? dirname($_SERVER['SCRIPT_NAME']);
        $this->files = new Files($_FILES ?? []);
        $this->headers = new Header;
        $this->arr = new Arr();
        $this->setMethod();
    }

    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '';
    }

    /**
     * @deprecated Use input() method instead
     */
    public function query(string $key = null, $default = null)
    {
        return $this->input($key, $default);
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
        return $this->scheme() . '://' . $this->hostWithPort() .  $this->fullpath();
    }

    public function fullUrl(): string
    {
        return $this->scheme() . '://' . $this->hostWithPort() .  $this->uri();
    }

    public function method(): string
    {
        return $this->method;
    }

    public function hostWithPort()
    {
        $hostWithPort = $this->host();

        if ($this->port()) {
            $hostWithPort .= ':' . $this->port();
        }

        return $hostWithPort;
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
        // Handle JSON requests
        if ($this->isJson()) {
            if (null === $this->jsonBody) {
                $this->parseJson();
            }
            return $key === null ? $this->jsonBody : $this->arr->get($key, $this->jsonBody, $default);
        }

        // For spoofed methods, use POST data
        if ($this->isSpoofed()) {
            return $key === null ? $_POST : $this->arr->get($key, $_POST, $default);
        }

        // Handle different HTTP methods
        $data = match ($this->method) {
            'GET' => $_GET,
            'POST' => $_POST,
            'PUT', 'PATCH', 'DELETE' => $this->getParsedBody(),
        };

        return $key === null ? $data : $this->arr->get($key, $data, $default);
    }

    /**
     * @deprecated Use input() method instead
     */
    public function json(?string $key = null, $default = null): mixed
    {
        return $this->input($key, $default);
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
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') == 0;
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
        $host = $_SERVER['HTTP_HOST'] ?? getenv('HTTP_HOST');

        return explode(':', $host)[0];
    }

    public function port(): ?int
    {
        return $_SERVER['SERVER_PORT'] ?? null;
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

    /**
     * Get the route parameters.
     * 
     * @return mixed The value of the specified parameter, or an array of all parameters if $key is null.
     */
    public function params(?string $key, $default = null)
    {
        if (is_null($key)) {
            return $this->route()->getParams();
        }

        return $this->route()->getParams()[$key] ?? $default;
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

    /**
     * @deprecated Internal use only, will be removed
     */
    private function queryData(string $key = null, $default = null)
    {
        return $this->input($key, $default);
    }

    /**
     * @deprecated Internal use only, will be removed
     */
    private function postData(string $key = null, $default = null)
    {
        return $this->input($key, $default);
    }
}
