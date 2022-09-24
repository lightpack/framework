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
    function app(string $key = null)
    {
        $container = \Lightpack\Container\Container::getInstance();

        return $key ? $container->get($key) : $container;
    }
}

if (!function_exists('redirect')) {
    /** 
     * Redirect to URI.
     */
    function redirect($uri = '/', $code = 302)
    {
        app('response')->redirect($uri, $code);
    }
}

if (!function_exists('csrf_input')) {
    /** 
     * Returns an HTML input for CSRF token.
     */
    function csrf_input(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . session()->token() . '">';
    }
}

if (!function_exists('_e')) {
    /**
     * HTML characters to entities converter.
     * 
     * Often used to escape HTML output to protect against 
     * XSS attacks..
     */
    function _e(string $str): string
    {
        return htmlentities($str, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_env')) {
    /**
     * Gets an environment variable.
     */
    function get_env(string $key, string $default = null): ?string
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return getenv($key) ? getenv($key) : $default;
    }
}

if (!function_exists('set_env')) {
    /**
     * Sets an environment variable.
     */
    function set_env(string $key, ?string $value): void
    {
        if (get_env($key) === null) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('route')) {
    /**
     * This function returns an instance of route object from
     * the app container.
     * 
     * @return \Lightpack\Routing\Route
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
    function auth(string $driver = null)
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
    function db(): \Lightpack\Database\Pdo
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

if (!function_exists('validator')) {
    /**
     * Returns an instance of validator.
     */
    function validator(array $input = [], array $rules = []): \Lightpack\Validator\Validator
    {
        $validator = new \Lightpack\Validator\Validator();

        if ($input) {
            $validator->setInput($input);
        }

        if ($rules) {
            $validator->setRules($rules);
        }

        return $validator;
    }
}
