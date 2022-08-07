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

    public function token()
    {
        return $this->manager->getAuthToken();
    }

    public function viaToken(): Result
    {
        $result = $this->manager->verify('bearer');

        if ($result->isSuccess()) {
            $this->manager->updateLastLogin();
        }

        return $result;
    }

    public function login()
    {
        $result = $this->manager->verify('form');

        if ($result->isSuccess()) {
            $this->manager->updateLogin();
        } else {
            $this->manager->flashError();
            return $this->manager->redirectLoginUrl();
        }

        $this->manager->persist();
        $this->manager->redirectLogin();
    }

    public function logout()
    {
        $this->manager->forgetRememberMeCookie();
        
        session()->destroy();

        $this->manager->redirectLogout();
    }

    public function recall()
    {
        if (session()->get('authenticated')) {
            $this->manager->redirectLogin();
        } else {
            $this->manager->checkRememberMe();
        }
    }

    public function id()
    {
        return $this->manager->getAuthId();
    }

    public function user(): Identity
    {
        return $this->manager->getAuthUser();
    }

    public function isLoggedIn(): bool
    {
        return session()->has('authenticated');
    }

    public function isGuest(): bool
    {
        return !$this->isLoggedIn();
    }

    public function attempt(): Result
    {
        return $this->manager->attempt();
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
        $this->authManager->extend($type, $authenticatorClass);

        return $this;
    }

    public function redirectLogin()
    {
        $this->manager->redirectLogin();
    }

    public function redirectLogout()
    {
        $this->manager->redirectLogout();
    }

    public function redirectLoginUrl()
    {
        $this->manager->redirectLoginUrl();
    }
}
