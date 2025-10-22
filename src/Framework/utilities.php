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

if(!function_exists('csrf_token')) {
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
     */
    function _e(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_env')) {
    /**
     * Gets an environment variable.
     */
    function get_env(string $key, ?string $default = null): ?string
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
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
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

if(!function_exists('old')) {
    /**
     * View helper that returns the old input value flashed in session.
     */
    function old(string $key, string|array $default = '', bool $escape = true): string|array
    {
        static $oldInput;
        
        if(!isset($oldInput)) {
            $oldInput = session()->flash('_old_input') ?? [];
        }

        $arr = new \Lightpack\Utils\Arr;
        
        $value = $arr->get($key, $oldInput, $default);

        if(is_null($value)) {
            return '';
        }

        if(is_array($value)) {
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

        if(!isset($errors)) {
            $errors = app('session')->flash('_validation_errors') ?? [];
        }
       
        return $errors[$key] ?? '';
    }
}

if(!function_exists('spoof_input')) {
    /**
     * Returns a hidden input field for the spoofed request method.
     */
    function spoof_input(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . $method . '">';
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