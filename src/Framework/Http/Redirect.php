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

    public function to(string $url, int $statusCode = 302, array $headers = []): void
    {
        $this->setRedirectUrl($url)
            ->setCode($statusCode)
            ->setHeaders($headers)
            ->setHeader('Location', $url)
            ->send();
    }

    public function route(string $name, array $params = [], int $statusCode = 302, array $headers = []): void
    {
        $url = $this->url->route($name, ...$params);

        $this->to($url, $statusCode, $headers);
    }

    public function back(int $statusCode = 302, array $headers = []): void
    {
        $url = $this->session->get('_previous_url', '/');

        $this->to($url, $statusCode, $headers);
    }

    public function intended(int $statusCode = 302, array $headers = []): void
    {
        $url = $this->session->get('_intended_url', '/');

        $this->session->delete('_intended_url');

        $this->to($url, $statusCode, $headers);
    }

    public function refresh(int $statusCode = 302, array $headers = []): void
    {
        $this->to($this->request->fullUrl(), $statusCode, $headers);
    }

    public function boot(Request $request, Session $session, Url $url)
    {
        $this->request = $request;
        $this->session = $session;
        $this->url = $url;
    }
}