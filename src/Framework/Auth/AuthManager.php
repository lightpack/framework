<?php

namespace Lightpack\Auth;

use Lightpack\Auth\Authenticators\BearerAuthenticator;
use Lightpack\Auth\Authenticators\CookieAuthenticator;
use Lightpack\Auth\Authenticators\FormAuthenticator;

class AuthManager
{
    protected $config = [];

    protected $normalizedConfig = [];

    protected $driver;

    /** @var Identity */
    protected static $identity;

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

    public function getAuthToken()
    {
        return self::$identity ? self::$identity->get($this->normalizedConfig['fields.api_token']) : null;
    }

    public function viaToken(): Result
    {
        $result = $this->verify('bearer');

        if ($result->isSuccess()) {
            $this->updateLastLogin();
        }

        return $result;
    }

    public function getAuthId()
    {
        return self::$identity->get($this->normalizedConfig['fields.id']);
    }

    public function getAuthUser(): Identity
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

    public function attempt(): Result
    {
        $result = $this->verify('form');

        if ($result->isSuccess()) {
            $this->updateLogin();
        }

        return $result;
    }

    public function setdriver(string $driver): self
    {
        if($driver !== $this->driver) {
            $this->driver = $driver;
            $this->normalizedConfig = $this->getNormalizedConfig();
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

    public function persist()
    {
        if (!self::$identity) {
            return;
        }

        $this->populateSession();

        $rememberTokenField = $this->normalizedConfig['fields.remember_token'];

        if (request()->post($rememberTokenField)) {
            cookie()->forever($rememberTokenField, self::$identity->get($rememberTokenField));
        }
    }

    public function flashError()
    {
        $message = $this->normalizedConfig['flash_error'];

        session()->flash('flash_error', $message);
    }

    public function checkRememberMe()
    {
        $result = $this->verify('cookie');

        if ($result->isSuccess()) {
            $this->persist();
            $this->updateLogin();
            $this->redirectLogin();
        }
    }

    public function updateLogin()
    {
        $idField = $this->normalizedConfig['fields.id'];
        $apiTokenField = $this->normalizedConfig['fields.api_token'];
        $lastLoginField = $this->normalizedConfig['fields.last_login_at'];
        $rememberTokenField = $this->normalizedConfig['fields.remember_token'];

        $fields[$lastLoginField] = date('Y-m-d H:i:s');

        if (request()->post($rememberTokenField)) {
            $fields[$rememberTokenField] = $this->generateRememberToken();
        } else {
            $fields[$apiTokenField] = $this->hashToken($this->generateApiToken());
        }

        $identifier = new $this->normalizedConfig['identifier'];

        $identifier->updateLogin(self::$identity->get($idField), $fields);
    }

    public function updateLastLogin()
    {
        $lastLoginField = $this->normalizedConfig['fields.last_login_at'];

        $fields[$lastLoginField] = date('Y-m-d H:i:s');

        $identifier = new $this->normalizedConfig['identifier'];
        $identifier->updateLogin(self::$identity->get($this->normalizedConfig['fields.id']), $fields);
    }

    protected function generateApiToken()
    {
        $token = self::$identity->get($this->normalizedConfig['fields.id']) . '|' . bin2hex(random_bytes(16));

        self::$identity->set($this->normalizedConfig['fields.api_token'], $token);

        return $token;
    }

    protected function generateRememberToken()
    {
        $rememberToken = bin2hex(random_bytes(16));
        $cookie = self::$identity->get($this->normalizedConfig['fields.id']). '|' . $rememberToken;

        self::$identity->set($this->normalizedConfig['fields.remember_token'], $cookie);

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

    public function verify(string $authenticatorType): Result
    {
        /** @var \Lightpack\Auth\Result */
        $result = $this->getAuthenticator($authenticatorType)->verify();

        if($result->isSuccess()) {
            self::$identity = $result->getIdentity();
        }

        return $result;
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

    public function forgetRememberMeCookie()
    {
        $rememberTokenField = $this->normalizedConfig['fields.remember_token'];

        cookie()->delete($rememberTokenField);
    }

    protected function getAuthenticator(string $authenticatorType): AbstractAuthenticator
    {
        if (!isset(self::$authenticators[$authenticatorType])) {
            throw new \Exception("Authenticator not found for auth driver: '{$authenticatorType}'");
        }

        $identifier = new $this->normalizedConfig['identifier'];
        $config = $this->normalizedConfig;
        $authenticatorClass = self::$authenticators[$authenticatorType];

        return new $authenticatorClass($identifier, $config);
    }
}
