<?php

namespace Lightpack\SocialAuth\Controllers;

use RuntimeException;
use Lightpack\SocialAuth\Models\SocialAccountModel;

class SocialAuthController
{
    public function redirect(string $provider)
    {
            $providerClass = $this->getProvider($provider);
            
            if (request()->expectsJson()) {
                $authUrl = $providerClass->stateless()->getAuthUrl();
                return response()->json(['auth_url' => $authUrl]);
            }
            
            // Web flow - use session
            session()->set('social_auth_provider', $provider);
            $authUrl = $providerClass->getAuthUrl();
            return redirect()->to($authUrl);
    }

    public function callback(string $provider)
    {
        try {
            $state = request()->query('state');
            if ($state) {
                $stateData = json_decode(base64_decode($state), true);
                if (($stateData['is_api'] ?? false)) {
                    return $this->handleApiCallback($provider);
                }
            }
            
            return $this->handleWebCallback($provider);
        } catch (\Exception $e) {
            return $this->handleCallbackError($e);
        }
    }

    protected function handleApiCallback(string $provider)
    {
        $state = request()->query('state');
        if (!$state) {
            throw new RuntimeException('Invalid authentication state', 401);
        }

        $stateData = json_decode(base64_decode($state), true);
        if (!isset($stateData['provider']) || $stateData['provider'] !== $provider || !($stateData['is_api'] ?? false)) {
            throw new RuntimeException('Invalid authentication state', 401);
        }

        $user = $this->authenticateUser($provider);
        
        return response()->json([
            'access_token' => $user->createToken($provider . '-api-auth'),
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    protected function handleWebCallback(string $provider) 
    {
        // Verify session state
        if ($provider !== session()->get('social_auth_provider')) {
            throw new RuntimeException('Invalid authentication state', 401);
        }

        $user = $this->authenticateUser($provider);
        
        auth()->loginAs($user);
        session()->delete('social_auth_provider');
        
        return redirect()->route('dashboard');
    }

    protected function authenticateUser(string $provider)
    {
        $code = request()->query('code');
        if (empty($code)) {
            throw new RuntimeException('Authorization code not received', 400);
        }

        $providerClass = $this->getProvider($provider);
        $providerUser = $providerClass->getUser($code);
        
        return $this->findOrCreateUser($provider, $providerUser);
    }

    protected function handleCallbackError(\Exception $e)
    {
        $code = $e->getCode() ?: 500;
        
        if (request()->expectsJson()) {
            return response()->json(['error' => $e->getMessage()], $code);
        }
        
        session()->flash('error', $e->getMessage());
        return redirect()->route('login');
    }

    protected function getProvider(string $providerKey)
    {
        $providerClass = config('social.providers.' . $providerKey . '.provider');

        if (!$providerClass) {
            throw new RuntimeException('Unsupported authentication provider: ' . $providerKey, 400);
        }

        return app()->resolve($providerClass);
    }

    protected function findOrCreateUser(string $provider, array $providerUser)
    {
        $account = SocialAccountModel::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerUser['id'])
            ->one();

        if ($account) {
            return $account->user;
        }

        // Create new user if not exists
        $userClass = config('social.user.provider');

        $user = (new $userClass)::query()
            ->where('email', $providerUser['email'])
            ->one();

        if (!$user) {
            $user = new $userClass;
            $user->name = $providerUser['name'];
            $user->email = $providerUser['email'];
            $user->password = password()->hash(bin2hex(random_bytes(32)));
            $user->email_verified_at = moment()->now();
            $user->save();
        }

        // Link social account
        $socialAccount = new SocialAccountModel;
        $socialAccount->user_id = $user->id;
        $socialAccount->provider = $provider;
        $socialAccount->provider_id = $providerUser['id'];
        $socialAccount->save();

        return $user;
    }
}
