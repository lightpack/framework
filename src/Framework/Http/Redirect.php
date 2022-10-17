<?php

namespace Lightpack\Http;

use Lightpack\Utils\Url;
use Lightpack\Session\Session;

class Redirect extends Response
{
    /** @var \Lightpack\Http\Request */
    protected $request;

    /** @var \Lightpack\Session\Session */
    protected $session;

    /** @var \Lightpack\Utils\Url */
    protected $url;

    public function to(string $url, int $statusCode = 302, array $headers = []): self
    {
        $this->setRedirectUrl($url);
        $this->setCode($statusCode);
        $this->setHeaders($headers);
        $this->setHeader('Location', $url);

        return $this;
    }

    public function route(string $name, array $params = [], int $statusCode = 302, array $headers = []): self
    {
        $url = $this->url->route($name, ...$params);

        return $this->to($url, $statusCode, $headers);
    }

    public function back(int $statusCode = 302, array $headers = []): self
    {
        $url = $this->session->get('_previous_url', '/');

        return $this->to($url, $statusCode, $headers);
    }

    public function intended(int $statusCode = 302, array $headers = []): self
    {
        $url = $this->session->get('_intended_url', '/');

        $this->session->delete('_intended_url');

        return $this->to($url, $statusCode, $headers);
    }

    public function refresh(int $statusCode = 302, array $headers = []): self
    {
        return $this->to($this->request->fullUrl(), $statusCode, $headers);
    }

    public function boot(Request $request, Session $session, Url $url)
    {
        $this->request = $request;
        $this->session = $session;
        $this->url = $url;
    }
}