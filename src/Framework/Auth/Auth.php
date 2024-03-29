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

    public function viaToken(): ?Identity
    {
        $identity = $this->manager->verify('bearer');

        if ($identity) {
            $this->manager->updateLastLogin();
        }

        return $identity;
    }

    public function login()
    {
        $identity = $this->manager->verify('form');

        if ($identity) {
            $this->manager->updateLogin();
        } else {
            $this->manager->flashError();
            return $this->manager->redirectLoginUrl();
        }

        $this->manager->persist();
        return $this->manager->redirectLogin();
    }

    public function logout()
    {
        $this->manager->forgetRememberMeCookie();

        session()->destroy();

        return $this->manager->redirectLogout();
    }

    public function recall()
    {
        if (session()->get('_logged_in')) {
            return $this->manager->redirectLogin();
        } else {
            return $this->manager->checkRememberMe();
        }
    }

    public function id()
    {
        return $this->manager->getAuthId();
    }

    public function user(): ?Identity
    {
        return $this->manager->getAuthUser();
    }

    public function isLoggedIn(): bool
    {
        return session()->get('_logged_in', false);
    }

    public function isGuest(): bool
    {
        $isGuest = !$this->isLoggedIn();

        if ($isGuest) {
            session()->set('_intended_url', request()->fullUrl());
        }

        return $isGuest;
    }

    public function attempt(): ?Identity
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
        return $this->manager->redirectLogin();
    }

    public function redirectLogout()
    {
        return $this->manager->redirectLogout();
    }

    public function redirectLoginUrl()
    {
        return $this->manager->redirectLoginUrl();
    }
}
