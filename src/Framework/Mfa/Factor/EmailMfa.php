<?php

namespace Lightpack\Mfa\Factor;

use Lightpack\Auth\Models\AuthUser;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\Mfa\MfaInterface;
use Lightpack\Mfa\Job\EmailMfaJob;
use Lightpack\Utils\Otp;

/**
 * Email-based MFA factor implementation.
 */
class EmailMfa implements MfaInterface
{
    public function __construct(
        protected Cache $cache,
        protected Config $config,
        protected Otp $otp,
    ) {}

    public function send(AuthUser $user): void
    {
        $this->cache->set(
            $this->getCacheKey($user),
            $code = $this->generateCode(),
            $this->config->get('mfa.email.ttl')
        );

        (new EmailMfaJob)->dispatch([
            'user' => $user->toArray(),
            'mfa_code' => $code,
        ]);
    }

    public function validate(AuthUser $user, ?string $input): bool
    {
        if(!$input) {
            return false;
        }

        $key = $this->getCacheKey($user);
        $code = $this->cache->get($key);
        if ($code && $input == $code) {
            $this->cache->delete($key); // One-time use
            return true;
        }
        return false;
    }

    public function getName(): string
    {
        return 'email';
    }

    protected function getCacheKey($user): string
    {
        return 'mfa_email_' . $user->id;
    }

    protected function generateCode(): string
    {
        return $this->otp
            ->length($this->config->get('mfa.email.code_length', 6))
            ->type($this->config->get('mfa.email.code_type', 'numeric'))
            ->generate();
    }
}
