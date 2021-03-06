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

if (!function_exists('url')) {
    /*
    * ------------------------------------------------------------
    * Creates a relative URL.
    * ------------------------------------------------------------
    * 
    * It takes any number of string texts to generate relative
    * URL to application basepath.
    */
    function url(string ...$fragments)
    {
        $path = implode('/', $fragments);
        $url = trim(app('request')->basepath(), '/') . '/' . trim($path, '/');

        return $url;
    }
}

if (!function_exists('redirect')) {
    /*
    * ------------------------------------------------------------
    * Redirect to URI.
    * ------------------------------------------------------------
    */
    function redirect($uri = '', $code = 302)
    {
        $uri = url($uri);
        header('Location: ' . $uri, true, $code);
        exit;
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
        return '<input type="hidden" name="csrf_token" value="' . app('session')->token() . '">';
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

if (!function_exists('slugify')) {
    /**
     * ------------------------------------------------------------     
     * Converts an ASCII text to URL friendly slug.
     * ------------------------------------------------------------      
     * 
     * It will replace any non-word character, non-digit 
     * character, or a non-dash '-' character with empty. 
     * Also it will replace any number of space character 
     * with a single dash '-'.
     */
    function slugify(string $text)
    {
        $text = preg_replace(
            ['#[^\w\d\s-]+#', '#(\s)+#'],
            ['', '-'],
            $text
        );

        return strtolower(trim($text, ' -'));
    }
}

if (!function_exists('asset_url')) {
    /**
     * ------------------------------------------------------------
     * Generates relaive URL to /public/assets folder.
     * ------------------------------------------------------------
     * 
     * Usage: 
     * 
     * asset_url('css', 'styles.css');
     * asset_url('img', 'favicon.png');
     * asset_url('js', 'scripts.js');
     */
    function asset_url(string $type, string $file): ?string
    {
        return url('assets', $type, $file);
    }
}

if (!function_exists('humanize')) {
    /**
     * ------------------------------------------------------------     
     * Converts a slug URL to friendly text.
     * ------------------------------------------------------------      
     * 
     * It replaces dashes and underscores with whitespace 
     * character. Then capitalizes the first character.
     */
    function humanize(string $slug)
    {
        $text = str_replace(['_', '-'], ' ', $slug);
        $text = trim($text);

        return ucfirst($text);
    }
}

if (!function_exists('query_url')) {
    /**
     * ------------------------------------------------------------
     * Generates relaive URL with support for query params.
     * ------------------------------------------------------------
     * 
     * For example:
     * 
     * query_url('users', ['sort' => 'asc', 'status' => 'active']);
     * 
     * That  will produce: /users?sort=asc&status=active 
     */
    function query_url(...$fragments): string
    {
        if (!$fragments) {
            return url();
        }

        $params = end($fragments);

        if (is_array($params)) {
            $query = '?' . http_build_query($params);
            array_pop($fragments);
        }

        return url(...$fragments) . $query;
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

if (!function_exists('underscore')) {
    /**
     * ------------------------------------------------------------     
     * Converts a string to underscored, lowercase form.
     * ------------------------------------------------------------      
     * 
     * For example: CustomerOrder => customer_order
     */
    function underscore(string $text)
    {
        $text = preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $text);

        return strtolower($text);
    }
}

if (!function_exists('camelize')) {
    /**
     * ------------------------------------------------------------     
     * Converts a string to its camelized form.
     * ------------------------------------------------------------      
     * 
     * For example: product thinker => ProductThinker
     */
    function camelize(string $text)
    {
        $text = ucwords(str_replace(['_', '-'], ' ', $text));
        $text = str_replace(' ', '', trim($text));

        return $text;
    }
}