<?php

namespace Lightpack\Auth;

use Lightpack\Http\Redirect;
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
        if(self::$identity) {
            return self::$identity->getAuthToken();
        }
    }

    public function viaToken(): ?Identity
    {
        $identity = $this->verify('bearer');

        if ($identity) {
            self::$identity = $identity;

            $this->updateLastLogin();
        }

        return $identity;
    }

    public function getAuthId()
    {
        $user = $this->getAuthUser();

        if ($user) {
            return $user->getId();
        }
    }

    public function getAuthUser(): ?Identity
    {
        if(!self::$identity) {
            $this->checkRememberMe();
        }

        return self::$identity;
    }

    public function redirectLogin(): Redirect
    {
        if (session()->has('_intended_url')) {
            return redirect()->intended();
        }

        $url = $this->normalizedConfig['login.redirect'];
        return redirect()->to($url);
    }

    public function redirectLogout(): Redirect
    {
        $url = $this->normalizedConfig['logout.redirect'];

        return redirect()->to($url);
    }

    public function redirectLoginUrl(): Redirect
    {
        $url = $this->normalizedConfig['login.url'];

        return redirect()->to($url);
    }

    public function attempt(): ?Identity
    {
        $identity = $this->verify('form');

        if ($identity) {
            self::$identity = $identity;

            $this->updateLogin();
        }

        return $identity;
    }

    public function setdriver(string $driver): self
    {
        if ($driver !== $this->driver) {
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

        if ($this->driver == 'default') {
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

        if (request()->input($rememberTokenField)) {
            cookie()->forever($rememberTokenField, self::$identity->getRememberToken());
        }
    }

    public function flashError()
    {
        $message = $this->normalizedConfig['flash_error'];

        session()->flash('flash_error', $message);
    }

    public function checkRememberMe()
    {
        $identity = $this->verify('cookie');

        if ($identity) {
            self::$identity = $identity;

            $this->persist();
            $this->updateLogin();

            return $this->redirectLogin();
        }

        return $this->redirectLoginUrl();
    }

    public function updateLogin()
    {
        $apiTokenField = $this->normalizedConfig['fields.api_token'];
        $lastLoginField = $this->normalizedConfig['fields.last_login_at'];
        $rememberTokenField = $this->normalizedConfig['fields.remember_token'];

        $fields[$lastLoginField] = date('Y-m-d H:i:s');

        if (request()->input($rememberTokenField)) {
            $fields[$rememberTokenField] = $this->generateRememberToken();
        } else {
            $fields[$apiTokenField] = $this->hashToken($this->generateApiToken());
        }

        /** @var Identifier */
        $identifier = $this->getIdentifier();

        $identifier->updateLogin(self::$identity->getId(), $fields);
    }

    public function updateLastLogin()
    {
        $lastLoginField = $this->normalizedConfig['fields.last_login_at'];

        $fields[$lastLoginField] = date('Y-m-d H:i:s');

        $identifier = $this->getIdentifier();
        
        $identifier->updateLogin(self::$identity->getId(), $fields);
    }

    protected function generateApiToken()
    {
        $token = self::$identity->getId() . '|' . bin2hex(random_bytes(16));

        self::$identity->setAuthToken($token);

        return $token;
    }

    protected function generateRememberToken()
    {
        $rememberToken = bin2hex(random_bytes(16));

        $cookie = self::$identity->getId() . '|' . $rememberToken;

        self::$identity->setRememberToken($cookie);

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

    public function verify(string $authenticatorType): ?Identity
    {
        $identity = $this->getAuthenticator($authenticatorType)->verify();

        if($identity) {
            self::$identity = $identity;
        }

        return $identity;
    }

    /**
     * Populates new user session.
     */
    public function populateSession()
    {
        session()->regenerate();
        session()->set('_logged_in', true);
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

        $identifier = $this->getIdentifier();
        $config = $this->normalizedConfig;
        $authenticatorClass = self::$authenticators[$authenticatorType];

        return new $authenticatorClass($identifier, $config);
    }

    protected function getIdentifier(): Identifier
    {
        if('default' === $this->driver) {
            $identifier = $this->normalizedConfig['identifier'];
            $model = $this->normalizedConfig['model'];

            return new $identifier(new $model);
        }

        throw new \Exception("Auth identifier not found for driver: '{$this->driver}'");
    }
}
