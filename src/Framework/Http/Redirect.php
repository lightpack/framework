<?php

namespace Lightpack\Http;

use Lightpack\Utils\Url;
use Lightpack\Session\Session;
use Lightpack\Routing\RouteRegistry;

class Redirect extends Response
{
    /** @var \Lightpack\Http\Request */
    protected $request;

    /** @var \Lightpack\Session\Session */
    protected $session;

    /** @var \Lightpack\Utils\Url */
    protected $url;

    /** @var \Lightpack\Routing\RouteRegistry */
    protected $routeRegistry;

    public function to(...$params): self
    {
        $url = $this->url->to(...$params);

        return $this->setRedirectUrl($url)
            ->setStatus(302)
            ->setMessage('Found')
            ->setHeader('Location', $url);
    }

    public function route(string $name, ...$params): self
    {
        $url = $this->routeRegistry->url($name, ...$params);

        return $this->setRedirectUrl($url)
            ->setStatus(302)
            ->setMessage('Found')
            ->setHeader('Location', $url);
    }

    public function back(): self
    {
        $url = $this->session->get('_previous_url', '/');

        return $this->setRedirectUrl($url)
            ->setStatus(302)
            ->setMessage('Found')
            ->setHeader('Location', $url);
    }

    public function intended(string $default = '/'): self
    {
        $url = $this->session->getIntendedUrl($default);
        $this->session->forgetIntendedUrl();

        return $this->setRedirectUrl($url)
            ->setStatus(302)
            ->setMessage('Found')
            ->setHeader('Location', $url);
    }

    public function intendedRoute(string $name, ...$params): self
    {
        if (!$this->session->hasIntendedUrl()) {
            return $this->route($name, ...$params);
        }
        
        return $this->intended();
    }

    public function refresh(): self
    {
        return $this->setRedirectUrl($this->request->fullUrl())
            ->setStatus(302)
            ->setMessage('Found')
            ->setHeader('Location', $this->request->fullUrl());
    }

    /**
     * @internal This method is for internal use only.
     */
    public function __boot(Request $request, Session $session, Url $url, RouteRegistry $routeRegistry): self
    {
        $this->request = $request;
        $this->session = $session;
        $this->url = $url;
        $this->routeRegistry = $routeRegistry;

        return $this;
    }
}
