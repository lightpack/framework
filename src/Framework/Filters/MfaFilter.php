<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\FilterInterface;

class MfaFilter implements FilterInterface
{
    public function before(Request $request, array $params = [])
    {
        $user = auth()->user();

        // Only if user is authenticated
        if(!$user) {
            return;
        }

        // Only if session has not set mfa_passed
        if(session()->get('mfa_passed')) {
            return;
        }

        // If MFA enforced or user has enabled MFA
        if(config('mfa.enforce') || $user->mfa_enabled) {
            $mfaProvider = config('mfa.default', 'null');
            app('mfa')->getFactor($mfaProvider)->send($user);
            return redirect()->route('mfa.verify.show');
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }
}