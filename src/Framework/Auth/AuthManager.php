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

    /** @var IdentityInterface|null */
    protected static ?IdentityInterface $identity = null;

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

    /**
     * Set the current identity
     * Used internally by auth system
     */
    public function setIdentity(IdentityInterface $identity): void 
    {
        self::$identity = $identity;
    }

    public function clearIdentity() {
        self::$identity = null;
    }

    public function viaToken(): ?IdentityInterface
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

    public function getAuthUser(): ?IdentityInterface
    {
        if(!self::$identity) {
            if(session()->get('_logged_in')) {
                return self::$identity = $this->getIdentifier()->findById(session()->get('_auth_id'));
            }

            $this->checkRememberMe();
        }

        return self::$identity;
    }


    public function attempt(): ?IdentityInterface
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

        if (request()->input('remember')) {
            // Duration in minutes (default: 30 days)
            $duration = $this->normalizedConfig['remember_duration'] ?? (60 * 24 * 30);
            cookie()->set('remember_token', self::$identity->getRememberToken(), $duration);
        }
    }


    public function checkRememberMe(): ?IdentityInterface
    {
        $identity = $this->verify('cookie');

        if ($identity) {
            self::$identity = $identity;

            $this->persist();
            $this->updateLogin();
        }

        return $identity;
    }

    public function updateLogin()
    {
        $fields['last_login_at'] = date('Y-m-d H:i:s');

        if (request()->input('remember')) {
            $fields['remember_token'] = $this->generateRememberToken();
        }

        /** @var Identifier */
        $identifier = $this->getIdentifier();

        $identifier->updateLogin(self::$identity->getId(), $fields);
    }

    public function updateLastLogin()
    {
        $fields['last_login_at'] = date('Y-m-d H:i:s');

        $identifier = $this->getIdentifier();
        
        $identifier->updateLogin(self::$identity->getId(), $fields);
    }

    protected function generateRememberToken()
    {
        $rememberToken = bin2hex(random_bytes(16));

        $cookie = self::$identity->getId() . '|' . $rememberToken;

        self::$identity->setRememberToken($cookie);

        return $rememberToken;
    }

    public function extend(string $type, string $authenticatorClass): self
    {
        self::$authenticators[$type] = $authenticatorClass;

        return $this;
    }

    public function verify(string $authenticatorType): ?IdentityInterface
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
        session()->set('_auth_id', self::$identity->getId());
    }

    public function forgetRememberMeCookie()
    {
        cookie()->delete('remember_token');
    }

    protected function getAuthenticator(string $authenticatorType): Authenticator
    {
        if (!isset(self::$authenticators[$authenticatorType])) {
            throw new \Exception("Authenticator not found for auth driver: '{$authenticatorType}'");
        }

        $identifier = $this->getIdentifier();
        $config = $this->normalizedConfig;
        $authenticatorClass = self::$authenticators[$authenticatorType];

        return new $authenticatorClass($identifier, $config);
    }

    protected function getIdentifier(): IdentifierInterface
    {
        if('default' === $this->driver) {
            $identifier = $this->normalizedConfig['identifier'];
            $model = $this->normalizedConfig['model'];

            return new $identifier(new $model);
        }

        throw new \Exception("Auth identifier not found for driver: '{$this->driver}'");
    }
}
