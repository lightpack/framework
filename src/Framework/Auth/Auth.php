<?php

namespace Lightpack\Auth;

class Auth
{
    /**
     * @var \Lightpack\Auth\AuthManager
     */
    protected $manager;

    public function __construct(string $driver, array $config)
    {
        $this->manager = new AuthManager($driver, $config);
    }

    public function viaToken(): ?IdentityInterface
    {
        $identity = $this->manager->verify('bearer');

        if ($identity) {
            $this->manager->updateLastLogin();
        }

        return $identity;
    }

    public function logout(): void
    {
        $this->manager->clearIdentity();
        $this->manager->forgetRememberMeCookie();

        session()->destroy();
    }

    public function recall(): ?IdentityInterface
    {
        if (session()->get('_logged_in')) {
            return $this->user();
        }

        return $this->manager->checkRememberMe();
    }

    public function id()
    {
        return $this->manager->getAuthId();
    }

    public function user(): ?IdentityInterface
    {
        return $this->manager->getAuthUser();
    }

    public function isLoggedIn(): bool
    {
        // Check if authenticated via API token (static identity)
        if ($this->manager->getAuthUser()) {
            return true;
        }
        
        // Check if authenticated via session (web login)
        return session()->get('_logged_in', false);
    }

    public function isGuest(): bool
    {
        return !$this->isLoggedIn();
    }

    public function attempt(): ?IdentityInterface
    {
        $identity = $this->manager->attempt();

        if ($identity) {
            $this->manager->persist();
        }

        return $identity;
    }

    public function setDriver(string $driver): self
    {
        $this->manager->setdriver($driver);

        return $this;
    }

    public function setConfig(array $config): self
    {
        $this->manager->setConfig($config);

        return $this;
    }

    public function extend(string $type, string $authenticatorClass): self
    {
        $this->manager->extend($type, $authenticatorClass);

        return $this;
    }

    /**
     * Login as a specific user without credentials.
     * Useful for testing and user impersonation.
     * 
     * @param IdentityInterface $user The user to login as
     * @return self For method chaining
     */
    public function loginAs(IdentityInterface $user): self
    {
        $this->manager->setIdentity($user);
        $this->manager->populateSession();
        $this->manager->updateLastLogin();

        return $this;
    }
}
