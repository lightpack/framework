<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\FilterInterface;

class VerifyEmailFilter implements FilterInterface
{
    public function before(Request $request, array $params = [])
    {
        /** @var \App\Models\UserModel $user */
        $user = auth()->user();

        if (!$user || !$user->email_verified_at) {
            if ($request->expectsJson()) {
                return response()->setStatus(403)->json([
                    'error' => 'Your email address is not verified.',
                ]);
            }

            return redirect()->route('verify.email');
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }
}
