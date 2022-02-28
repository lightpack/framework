<?php

namespace Lightpack\Auth;

use Lightpack\Auth\Authenticators\BearerAuthenticator;
use Lightpack\Auth\Authenticators\CookieAuthenticator;
use Lightpack\Auth\Authenticators\FormAuthenticator;

class Auth
{
    protected $config = [];
    protected $normalizedConfig = [];
    protected $driver;

    /** @var Identity */
    protected static $identity;

    protected static $token;
    protected static $cookie;
    protected static $rememberToken;
    protected static $authenticators = [
        'bearer' => BearerAuthenticator::class,
        'cookie' => CookieAuthenticator::class,
        'form' => FormAuthenticator::class,
    ];

    public function __construct(string $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;
        $this->normalizedConfig = $this->getNormalizedConfig($config);
    }

    public function token()
    {
        return self::$token;
    }

    public function viaToken()
    {
        $identity = $this->verify('bearer');
        $success = (bool) $identity;

        if ($identity) {
            self::$identity = $identity;
            $this->updateLastLogin();
        }

        return $success;
    }

    public function login()
    {
        $success = $this->attempt();

        if (!$success) {
            $this->flashError();
            $this->redirectLoginUrl();
        }

        $this->persist();
        $this->redirectLogin();
    }

    public function logout()
    {
        cookie()->delete('remember_me');
        session()->destroy();

        $this->redirectLogout();
    }

    public function recall()
    {
        if (session()->get('authenticated')) {
            $this->redirectLogin();
        } else {
            $this->checkRememberMe();
        }
    }

    public function id(string $identifierKey = 'id')
    {
        return self::$identity->get($identifierKey);
    }

    public function user()
    {
        return self::$identity;
    }

    public function redirectLogin()
    {
        $url = $this->normalizedConfig['login.redirect'];
        redirect($url);
    }

    public function redirectLogout()
    {
        $url = $this->normalizedConfig['logout.redirect'];

        redirect($url);
    }

    public function redirectLoginUrl()
    {
        $url = $this->normalizedConfig['login.url'];
        redirect($url);
    }

    public function attempt()
    {
        $identity = $this->verify('form');
        $success = (bool) $identity;

        if ($identity) {
            self::$identity = $identity;
            $this->updateLogin();
        }

        return $success;
    }

    public function setdriver(string $driver): self
    {
        if($driver !== $this->driver) {
            $this->normalizedConfig = $this->getNormalizedConfig();
            $this->driver = $driver;
        }

        return $this;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
        $this->normalizedConfig = $this->getNormalizedConfig();

        return $this;
    }

    public function getNormalizedConfig()
    {
        if (!isset($this->normalizedConfig)) {
            throw new \Exception("Configuration not found for auth driver: '{$this->driver}'");
        }

        if($this->driver == 'default') {
            $config = $this->config['default'];
        } else {
            $config = array_merge($this->config['default'], $this->config[$this->driver]);
        }

        return $config;
    }

    protected function persist()
    {
        if (!self::$identity) {
            return;
        }

        $this->populateSession();

        if (request()->post('remember_me')) {
            cookie()->forever('remember_me', self::$cookie);
        }
    }

    protected function flashError()
    {
        $message = $this->normalizedConfig['flash_error'];

        session()->flash('flash_error', $message);
    }

    protected function checkRememberMe()
    {
        $success = $this->verify('cookie');

        if ($success) {
            self::$identity = $success;

            $this->persist();
            $this->updateLogin();
            $this->redirectLogin();
        }
    }

    protected function updateLogin()
    {
        $fields['last_login_at'] = date('Y-m-d H:i:s');

        if (request()->post('remember_me')) {
            $fields['remember_token'] = $this->generateRememberToken();
        } else {
            $fields['api_token'] = $this->hashToken($this->generateApiToken());
        }

        $identifier = new $this->normalizedConfig['identifier'];
        $identifier->updateLogin(self::$identity->get('id'), $fields);
    }

    public function updateLastLogin()
    {
        $fields['last_login_at'] = date('Y-m-d H:i:s');

        $identifier = new $this->normalizedConfig['identifier'];
        $identifier->updateLogin(self::$identity->get('id'), $fields);
    }

    protected function generateApiToken()
    {
        $token = self::$identity->get('id') . '|' . bin2hex(random_bytes(16));
        self::$token = $token;

        return $this->token();
    }

    protected function generateRememberToken()
    {
        $rememberToken = bin2hex(random_bytes(16));
        $cookie = self::$identity->get('id'). '|' . $rememberToken;

        self::$cookie = $cookie;
        return $rememberToken;
    }

    protected function hashToken(string $token): string
    {
        return hash_hmac('sha1', $token, '');
    }

    public function extend(string $type, string $authenticatorClass): self
    {
        self::$authenticators[$type] = $authenticatorClass;

        return $this;
    }

    public function verify(string $authenticatorType)
    {
        $authenticator = new self::$authenticators[$authenticatorType];
        $config = $this->getNormalizedConfig();
        $identifier = new $config['identifier'];
        
        return $authenticator->verify($identifier, $config);
    }

    /**
     * Populates new user session.
     */
    public function populateSession()
    {
        session()->regenerate();
        session()->set('authenticated', true);
        session()->set('user', self::$identity);
    }
}
