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

    public function __construct(?string $basepath = null)
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
     * Retrieves query string parameters from $_GET superglobal.
     *
     * @param string|null $key     The query parameter key to retrieve. If null, returns all query parameters.
     * @param mixed      $default  The default value to return if the key is not found.
     *
     * @return mixed|array Returns the value for the specified key if found, the default value if key not found,
     *                     or the entire $_GET array if no key is specified.
     */
    public function query(?string $key = null, $default = null)
    {
        if (null === $key) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
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

    public function segments(?int $index = null)
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
        // Handle JSON requests data
        if ($this->isJson()) {
            if (null === $this->jsonBody) {
                $this->parseJson();
            }
            // Merge query params and JSON body, JSON body takes precedence
            $data = array_merge($_GET, $this->jsonBody ?? []);
            return $key === null ? $data : $this->arr->get($key, $data, $default);
        }

        // For spoofed methods, merge POST and query params (POST takes precedence)
        if ($this->isSpoofed()) {
            $data = array_merge($_GET, $_POST);
            return $key === null ? $data : $this->arr->get($key, $data, $default);
        }

        // Handle different HTTP methods
        $data = match ($this->method) {
            'GET' => $_GET,
            'POST' => array_merge($_GET, $_POST), // POST takes precedence
            'PUT', 'PATCH', 'DELETE' => array_merge($_GET, $this->getParsedBody()), // Parsed body takes precedence
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

    /**
     * Check if the incoming data is JSON.
     */
    public function isJson(): bool
    {
        if (strpos($this->format(), 'application/json') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Check if the client expects JSON data.
     */
    public function expectsJson(): bool
    {
        if ($this->hasHeader('Accept')) {
            return strpos($this->header('Accept'), 'application/json') !== false;
        }

        return false;
    }

    public function isSecure(): bool
    {
        return $this->scheme() == 'https';
    }

    public function scheme()
    {
        // Check forwarded proto from load balancer first
        if ($this->headers->has('X-Forwarded-Proto')) {
            return $this->headers->get('X-Forwarded-Proto');
        }

        if (
            (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') ||
            (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == '443'))
        ) {
            return 'https';
        }

        return 'http';
    }

    public function host(): string
    {
        // Check forwarded host from load balancer
        if ($this->headers->has('X-Forwarded-Host')) {
            $hosts = explode(',', $this->headers->get('X-Forwarded-Host'));
            // Strip port if present
            return explode(':', trim($hosts[0]))[0];
        }

        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        // Strip port if present
        return explode(':', $host)[0];
    }

    public function port(): ?int
    {
        // Check forwarded port from load balancer
        if ($this->headers->has('X-Forwarded-Port')) {
            return (int) $this->headers->get('X-Forwarded-Port');
        }

        // Try to extract port from forwarded host
        if ($this->headers->has('X-Forwarded-Host')) {
            $hosts = explode(',', $this->headers->get('X-Forwarded-Host'));
            $parts = explode(':', trim($hosts[0]));
            if (isset($parts[1])) {
                return (int) $parts[1];
            }
        }

        // check APP_URL for port
        $appUrl = get_env('APP_URL');
        if ($appUrl) {
            $parts = parse_url($appUrl);
            if (isset($parts['port'])) {
                return (int) $parts['port'];
            }
        }

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

    public function header(string $key, ?string $default = null): ?string
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
     * Get the value of the User-Agent header sent by the client.
     *
     * @return string|null The User-Agent string, or null if not present.
     */
    public function userAgent(): ?string
    {
        return $this->header('User-Agent');
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

    public function csrfToken(): ?string
    {
        // Check headers first (for AJAX/API requests)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        return $token ?? $this->input('_token');
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

    public function setMethod(?string $method = null): self
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
    private function queryData(?string $key = null, $default = null)
    {
        return $this->input($key, $default);
    }

    /**
     * @deprecated Internal use only, will be removed
     */
    private function postData(?string $key = null, $default = null)
    {
        return $this->input($key, $default);
    }

    /**
     * Get the client's IP address.
     * 
     * @throws \RuntimeException if IP address cannot be determined
     */
    public function ip(): string
    {
        // Check X-Forwarded-For from load balancer/proxy
        if ($this->headers->has('X-Forwarded-For')) {
            $ips = explode(',', $this->headers->get('X-Forwarded-For'));
            return trim($ips[0]);
        }

        // Check X-Real-IP from nginx
        if ($this->headers->has('X-Real-IP')) {
            return $this->headers->get('X-Real-IP');
        }

        if (!isset($_SERVER['REMOTE_ADDR'])) {
            throw new \RuntimeException('Could not determine client IP address');
        }

        return $_SERVER['REMOTE_ADDR'];
    }
}
