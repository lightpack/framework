<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\Authenticator;
use Lightpack\Auth\IdentityInterface;
use Lightpack\Auth\Models\AccessToken;

class BearerAuthenticator extends Authenticator
{
    public function verify(): ?IdentityInterface
    {
        $token = request()->bearerToken();

        if (null === $token) {
            return null;
        }

        $tokenHash = hash('sha256', $token);
        
        // Find token and check expiration with proper SQL grouping
        $accessToken = AccessToken::query()
            ->where('token', $tokenHash)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', date('Y-m-d H:i:s'));
            })
            ->orderBy('id', 'DESC')
            ->one();

        if (!$accessToken) {
            return null;
        }

        // Update last used timestamp
        $accessToken->last_used_at = date('Y-m-d H:i:s');
        $accessToken->save();

        $user = $accessToken->user;
        
        // Set the current access token on the user
        $user->currentAccessToken = $accessToken;

        return $user;
    }
}
