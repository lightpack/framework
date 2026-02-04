<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before(Request $request, array $params = [])
    {
        $type = $params[0] ?? 'web';

        if ('web' === $type && auth()->isGuest()) {
            if ($request->isGet()) {
                session()->setIntendedUrl(request()->fullUrl());
            }

            // Try remember-me, if it fails, redirect to login
            if (!auth()->recall()) {
                $guestRoute = config('auth.routes.guest', 'login');
                return redirect()->route($guestRoute);
            }
        }

        if ('api' === $type && null === auth()->viaToken()) {
            return response()->setStatus(401)->json([
                'error' => 'Unauthorized',
            ]);
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }
}
