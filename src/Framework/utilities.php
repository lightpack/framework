<?php

if (!function_exists('app')) {
    /*
    * ------------------------------------------------------------
    * Shortcut to $container->get() method.
    * ------------------------------------------------------------
    */
    function app(string $key)
    {
        global $container;
        return $container ? $container->get($key) : null;
    }
}

if (!function_exists('redirect')) {
    /*
    * ------------------------------------------------------------
    * Redirect to URI.
    * ------------------------------------------------------------
    */
    function redirect($uri = '/', $code = 302)
    {
        app('response')->redirect($uri, $code);
    }
}

if (!function_exists('csrf_input')) {
    /*
    * ------------------------------------------------------------
    * Returns an HTML input for CSRF token.
    * ------------------------------------------------------------
    */
    function csrf_input()
    {
        return '<input type="hidden" name="csrf_token" value="' . session()->token() . '">';
    }
}

if (!function_exists('_e')) {
    /**
     * ------------------------------------------------------------     
     * HTML characters to entities converter.
     * ------------------------------------------------------------     
     * 
     * Often used to escape HTML output to protect against 
     * XSS attacks..
     */
    function _e(string $str)
    {
        return htmlentities($str, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_env')) {
    /**
     * ------------------------------------------------------------
     * Gets an environment variable.
     * ------------------------------------------------------------
     */
    function get_env($key, $default = null)
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
     * ------------------------------------------------------------
     * Sets an environment variable.
     * ------------------------------------------------------------
     */
    function set_env($key, $value)
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
     * ------------------------------------------------------------     
     * Route function.
     * ------------------------------------------------------------      
     * 
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
     * ------------------------------------------------------------
     * Pretty dump using var_dump()
     * ------------------------------------------------------------
     * 
     */
    function dd(...$args)
    {
        $renderer = new Lightpack\Debug\Dumper;

        $renderer->varDump($args);

        die;
    }
}

if (!function_exists('pp')) {
    /**
     * ------------------------------------------------------------
     * Pretty print using print_r()
     * ------------------------------------------------------------
     * 
     */
    function pp(...$args)
    {
        $renderer = new Lightpack\Debug\Dumper;

        $renderer->printDump($args);

        die;
    }
}

if (!function_exists('request')) {
    /**
     * ------------------------------------------------------------
     * Returns the current request object.
     * ------------------------------------------------------------
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
     * ------------------------------------------------------------
     * Returns a new instance of response.
     * ------------------------------------------------------------
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
     * ------------------------------------------------------------
     * Returns the session object.
     * ------------------------------------------------------------
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
     * ------------------------------------------------------------
     * Returns a new instance of cookie.
     * ------------------------------------------------------------
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
     * ------------------------------------------------------------
     * Returns the event object.
     * ------------------------------------------------------------
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
     * ------------------------------------------------------------
     * Returns the cache object.
     * ------------------------------------------------------------
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
     * ------------------------------------------------------------
     * Returns the logger object.
     * ------------------------------------------------------------
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
     * ------------------------------------------------------------
     * Returns the auth object.
     * ------------------------------------------------------------
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
     * ------------------------------------------------------------
     * Gets config data.
     * ------------------------------------------------------------
     */
    function config($key, $default = null)
    {
        return app('config')->get($key, $default);
    }
}

if (!function_exists('db')) {
    /**
     * ------------------------------------------------------------
     * Returns PDO database connection instance.
     * ------------------------------------------------------------
     */
    function db(): \Lightpack\Database\Pdo
    {
        return app('db');
    }
}

if (!function_exists('template')) {
    /**
     * ------------------------------------------------------------
     * Returns an instance of view template.
     * ------------------------------------------------------------
     */
    function template(): \Lightpack\View\Template
    {
        return app('template');
    }
}
