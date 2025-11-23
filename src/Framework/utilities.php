<?php

if (!function_exists('app')) {
    /**
     * ------------------------------------------------------------
     * Resolves a binding from IoC container if $key is provided.
     * Otherwise, returns the IoC container instance.
     * ------------------------------------------------------------
     *
     * @return Lightpack\Container\Container|mixed
     */
    function app(?string $key = null)
    {
        $container = \Lightpack\Container\Container::getInstance();

        if (null === $key) {
            return $container;
        }

        if ($container->has($key)) {
            return $container->get($key);
        }

        return $container->resolve($key);
    }
}

if (!function_exists('redirect')) {
    /** 
     * Redirects to the given URI. If URI is not provided, returns the 
     * redirect instance.
     * 
     * @return Lightpack\Http\Redirect
     */
    function redirect()
    {
        return app('redirect');
    }
}

if (!function_exists('csrf_input')) {
    /** 
     * Returns an HTML input for CSRF token.
     */
    function csrf_input(): string
    {
        return '<input type="hidden" name="_token" value="' . session()->token() . '">';
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Returns the CSRF token.
     */
    function csrf_token(): string
    {
        return session()->token();
    }
}

if (!function_exists('_e')) {
    /**
     * Escape HTML special characters to protect against XSS attacks.
     * 
     * Converts: & < > " ' to their HTML entity equivalents.
     * Preserves UTF-8 characters (é, ñ, etc.) as-is.
     * Returns empty string if null is provided.
     */
    function _e(?string $str): string
    {
        if ($str === null) {
            return '';
        }

        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_env')) {
    /**
     * Gets an environment variable.
     */
    function get_env(string $key, mixed $default = null): mixed
    {
        return Lightpack\Config\Env::get($key, $default);
    }
}

if (!function_exists('set_env')) {
    /**
     * Sets an environment variable.
     */
    function set_env(string $key, mixed $value): void
    {
        Lightpack\Config\Env::set($key, $value);
    }
}

if (!function_exists('route')) {
    /**
     * This function returns an instance of route object from
     * the app container.
     * 
     * @return \Lightpack\Routing\RouteRegistry
     */
    function route()
    {
        return app('route');
    }
}

if (!function_exists('dd')) {
    /**
     * Pretty dump using var_dump()
     */
    function dd(...$args): void
    {
        $renderer = new Lightpack\Debug\Dumper;

        $renderer->varDump($args);

        die;
    }
}

if (!function_exists('pp')) {
    /**
     * Pretty print using print_r()
     */
    function pp(...$args): void
    {
        $renderer = new Lightpack\Debug\Dumper;

        $renderer->printDump($args);

        die;
    }
}

if (!function_exists('request')) {
    /**
     * Returns the current request object.
     * 
     * @return \Lightpack\Http\Request
     */
    function request()
    {
        return app('request');
    }
}

if (!function_exists('response')) {
    /**
     * Returns a new instance of response.
     * 
     * @return \Lightpack\Http\Response
     */
    function response()
    {
        return app('response');
    }
}

if (!function_exists('session')) {
    /**
     * Returns the session object.
     * 
     * @return \Lightpack\Session\Session
     */
    function session()
    {
        return app('session');
    }
}

if (!function_exists('cookie')) {
    /**
     * Returns a new instance of cookie.
     * 
     * @return \Lightpack\Http\Cookie
     */
    function cookie()
    {
        return app('cookie');
    }
}

if (!function_exists('event')) {
    /**
     * Returns the event object.
     * 
     * @return \Lightpack\Event\Event
     */
    function event()
    {
        return app('event');
    }
}

if (!function_exists('cache')) {
    /**
     * Returns the cache object.
     * 
     * @return \Lightpack\Cache\Cache
     */
    function cache()
    {
        return app('cache');
    }
}

if (!function_exists('logger')) {
    /**
     * Returns the logger object.
     * 
     * @return \Lightpack\Logger\Logger
     */
    function logger()
    {
        return app('logger');
    }
}

if (!function_exists('auth')) {
    /**
     * Returns the auth object.
     * 
     * @return \Lightpack\Auth\Auth
     */
    function auth(?string $driver = null)
    {
        if (!$driver) {
            return app('auth');
        }

        return app('auth')->setDriver($driver);
    }
}

if (!function_exists('config')) {
    /**
     * Gets config data.
     */
    function config($key, $default = null)
    {
        return app('config')->get($key, $default);
    }
}

if (!function_exists('db')) {
    /**
     * Returns PDO database connection instance.
     */
    function db(): \Lightpack\Database\DB
    {
        return app('db');
    }
}

if (!function_exists('template')) {
    /**
     * Returns an instance of view template.
     */
    function template(): \Lightpack\View\Template
    {
        return app('template');
    }
}

if (!function_exists('url')) {
    /**
     * Returns an instance of Url utility.
     */
    function url(): \Lightpack\Utils\Url
    {
        if (false === app()->has('url')) {
            return app()->instance('url', new \Lightpack\Utils\Url);
        }

        return app('url');
    }
}

if (!function_exists('str')) {
    /**
     * Returns an instance of Str utility.
     */
    function str(): \Lightpack\Utils\Str
    {
        if (false === app()->has('str')) {
            return app()->instance('str', new \Lightpack\Utils\Str);
        }

        return app('str');
    }
}

if (!function_exists('arr')) {
    /**
     * Returns an instance of Arr utility.
     */
    function arr(): \Lightpack\Utils\Arr
    {
        if (false === app()->has('arr')) {
            return app()->instance('arr', new \Lightpack\Utils\Arr);
        }

        return app('arr');
    }
}

if (!function_exists('moment')) {
    /**
     * Returns an instance of Moment utility.
     */
    function moment(): \Lightpack\Utils\Moment
    {
        if (false === app()->has('moment')) {
            return app()->instance('moment', new \Lightpack\Utils\Moment);
        }

        return app('moment');
    }
}

if (!function_exists('password')) {
    /**
     * Returns an instance of Password utility.
     */
    function password(): \Lightpack\Utils\Password
    {
        if (false === app()->has('password')) {
            return app()->instance('password', new \Lightpack\Utils\Password);
        }

        return app('password');
    }
}

if (!function_exists('crypto')) {
    /**
     * Returns an instance of Crypto utility.
     */
    function crypto(): \Lightpack\Utils\Crypto
    {
        return app('crypto');
    }
}

if (!function_exists('validator')) {
    /**
     * Returns an instance of validator.
     */
    function validator(): \Lightpack\Validation\Validator
    {
        return app('validator');
    }
}

if (!function_exists('schedule')) {
    /**
     * Returns the task scheduler instance.
     */
    function schedule(): \Lightpack\Schedule\Schedule
    {
        return app('schedule');
    }
}

if (!function_exists('old')) {
    /**
     * View helper that returns the old input value flashed in session.
     */
    function old(string $key, string|array|null $default = '', bool $escape = true): string|array
    {
        static $oldInput;

        if (!isset($oldInput)) {
            $oldInput = session()->flash('_old_input') ?? [];
        }

        $arr = new \Lightpack\Utils\Arr;

        $value = $arr->get($key, $oldInput, $default);

        // Convert null to empty string for safe output
        if (is_null($value)) {
            return '';
        }

        if (is_array($value)) {
            return $value;
        }

        return $escape ? _e($value) : $value;
    }
}

if (!function_exists('error')) {
    /**
     * View helper that returns the validation error flashed in session.
     */
    function error(string $key): string
    {
        static $errors;

        if (!isset($errors)) {
            $errors = app('session')->flash('_validation_errors') ?? [];
        }

        return $errors[$key] ?? '';
    }
}

if (!function_exists('spoof_input')) {
    /**
     * Returns a hidden input field for the spoofed request method.
     */
    function spoof_input(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . $method . '">';
    }
}

if (!function_exists('hidden_input')) {
    /**
     * Returns a hidden input field with the given name and value.
     * Automatically escapes the value for security.
     */
    function hidden_input(string $name, string $value = ''): string
    {
        return '<input type="hidden" name="' . _e($name) . '" value="' . _e($value) . '">';
    }
}

if (!function_exists('form_open')) {
    /**
     * Opens a form with automatic CSRF token injection for POST/PUT/PATCH/DELETE.
     * 
     * @param string $action Form action URL
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param array $attributes Additional HTML attributes like classes, id, etc
     * @param bool $csrf Whether to include CSRF token (default: true)
     */
    function form_open(
        string $action = '',
        string $method = 'POST',
        array $attributes = [],
        bool $csrf = true
    ): string {
        $method = strtoupper($method);
        $spoofMethods = ['PUT', 'PATCH', 'DELETE'];
        $actualMethod = in_array($method, $spoofMethods) ? 'POST' : $method;

        $attrs = ['action' => $action, 'method' => $actualMethod];
        $attrs = array_merge($attrs, $attributes);

        $html = '<form';
        foreach ($attrs as $key => $value) {
            $html .= ' ' . _e($key) . '="' . _e($value) . '"';
        }
        $html .= '>';

        // Add CSRF token for state-changing methods
        if ($csrf && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $html .= "\n    " . csrf_input();
        }

        // Add method spoofing for PUT/PATCH/DELETE
        if (in_array($method, $spoofMethods)) {
            $html .= "\n    " . spoof_input($method);
        }

        return $html;
    }
}

if (!function_exists('form_close')) {
    /**
     * Closes a form tag.
     */
    function form_close(): string
    {
        return '</form>';
    }
}

if (!function_exists('storage')) {
    /**
     * Returns the storage object.
     */
    function storage(): \Lightpack\Storage\Storage
    {
        return app('storage');
    }
}

if (!function_exists('asset')) {
    /**
     * Return an instance of asset utility provider.
     */
    function asset(): \Lightpack\Utils\Asset
    {
        if (false === app()->has('asset')) {
            return app()->instance('asset', new \Lightpack\Utils\Asset);
        }

        return app('asset');
    }
}

if (!function_exists('js')) {
    /**
     * Return an instance of Js utility provider.
     */
    function js(): \Lightpack\Utils\Js
    {
        if (false === app()->has('js')) {
            return app()->instance('js', new \Lightpack\Utils\Js);
        }

        return app('js');
    }
}

if (!function_exists('limiter')) {
    /**
     * Return an instance of rate limiter utility.
     */
    function limiter(): \Lightpack\Utils\Limiter
    {
        if (false === app()->has('limiter')) {
            return app()->instance('limiter', new \Lightpack\Utils\Limiter);
        }

        return app('limiter');
    }
}

if (!function_exists('lock')) {
    /**
     * Return an instance of lock utility.
     */
    function lock(): \Lightpack\Utils\Lock
    {
        if (false === app()->has('lock')) {
            return app()->instance('lock', new \Lightpack\Utils\Lock);
        }

        return app('lock');
    }
}

if (!function_exists('url')) {
    /**
     * Return an instance of URL utility.
     */
    function url(): \Lightpack\Utils\Url
    {
        if (false === app()->has('url')) {
            return app()->instance('url', new \Lightpack\Utils\Url);
        }

        return app('url');
    }
}

if (!function_exists('captcha')) {
    /**
     * Return configured instance of captcha provider.
     */
    function captcha(): \Lightpack\Captcha\CaptchaInterface
    {
        return app('captcha');
    }
}

if (!function_exists('ai')) {
    /**
     * Return configured instance of AI provider.
     */
    function ai(): \Lightpack\AI\AI
    {
        return app('ai');
    }
}

if (!function_exists('sms')) {
    /**
     * Return configured instance of sms provider.
     */
    function sms(): \Lightpack\Sms\Sms
    {
        return app('sms');
    }
}

if (!function_exists('halt')) {
    /**
     * Halt execution and throw an HTTP exception with the given status code.
     * 
     * If no message is provided, uses the standard HTTP status message.
     * 
     * @param int $code HTTP status code (e.g., 404, 403, 500)
     * @param string $message Optional error message
     * @param array $headers Optional HTTP headers
     * @throws \Lightpack\Exceptions\HttpException
     */
    function halt(int $code, string $message = '', array $headers = []): void
    {
        // Use standard HTTP status message if no custom message provided
        if ($message === '') {
            $message = \Lightpack\Http\Response::STATUS_MESSAGES[$code] ?? 'HTTP Error';
        }

        $exception = new \Lightpack\Exceptions\HttpException($message, $code);

        if ($headers) {
            $exception->setHeaders($headers);
        }

        throw $exception;
    }
}

if (!function_exists('pipeline')) {
    /**
     * Create a new pipeline instance.
     *
     * @param mixed $passable The data to pass through the pipeline
     * @return \Lightpack\Utils\Pipeline
     */
    function pipeline($passable): \Lightpack\Utils\Pipeline
    {
        return new \Lightpack\Utils\Pipeline($passable);
    }
}

if (!function_exists('once')) {
    /**
     * Execute a callback only once per request and cache the result.
     * Subsequent calls with the same callback instance return the cached result.
     *
     * @param callable $callback The callback to execute
     * @return mixed The cached result
     */
    function once(callable $callback)
    {
        static $cache = null;

        if ($cache === null) {
            $cache = new \SplObjectStorage();
        }

        // Use object storage to track unique closures
        if (!$cache->contains($callback)) {
            $cache[$callback] = $callback();
        }

        return $cache[$callback];
    }
}

if (!function_exists('optional')) {
    /**
     * Safely access properties/methods on a potentially null value.
     * Returns null if the value is null, otherwise returns the value.
     *
     * @param mixed $value The value to wrap
     * @param callable|null $callback Optional callback to execute on non-null value
     * @return mixed
     */
    function optional($value, ?callable $callback = null)
    {
        if (is_null($value)) {
            return new class {
                public function __get($key)
                {
                    return $this; // Return self for chaining
                }

                public function __call($method, $args)
                {
                    return $this; // Return self for chaining
                }

                public function __toString()
                {
                    return '';
                }

                public function __isset($key)
                {
                    return false;
                }
            };
        }

        return $callback ? $callback($value) : $value;
    }
}
