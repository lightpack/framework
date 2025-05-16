<?php

namespace Lightpack\Mfa\Factor;

use Lightpack\Auth\Models\AuthUser;
use Lightpack\Cache\Cache;
use Lightpack\Sms\Sms;
use Lightpack\Config\Config;
use Lightpack\Mfa\MfaInterface;
use Lightpack\Utils\Otp;
use RuntimeException;

/**
 * Email-based MFA factor implementation.
 */
class SmsMfa implements MfaInterface
{
    public function __construct(
        protected Cache $cache,
        protected Config $config,
        protected Otp $otp,
        protected Sms $sms,
    ) {}

    public function send(AuthUser $user): void
    {
        $this->enforceResendRateLimit($user);

        $this->cache->set(
            $this->getCacheKey($user),
            $code = $this->generateCode(),
            $this->config->get('mfa.sms.ttl')
        );

        $message = $this->config->get('mfa.sms.message', 'Your verification code is: {code}');
        $message = str_replace('{code}', $code, $message);

        $this->sms->send($user->phone, $message);
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
        return 'sms';
    }

    protected function getCacheKey(AuthUser $user): string
    {
        return 'mfa_sms_' . $user->id;
    }

    protected function generateCode(): string
    {
        $bypass = $this->config->get('mfa.sms.bypass_code');
        if ($bypass) {
            return $bypass;
        }

        return $this->otp
            ->length($this->config->get('mfa.sms.code_length', 6))
            ->type($this->config->get('mfa.sms.code_type', 'numeric'))
            ->generate();
    }

    /**
     * Enforces the resend rate limit for MFA code requests.
     * Throws a RuntimeException if the user must wait before resending.
     */
    protected function enforceResendRateLimit(AuthUser $user): void
    {
        $maxAttempts = $this->config->get('mfa.sms.resend_max', 1);
        $intervalSeconds = $this->config->get('mfa.sms.resend_interval', 10);
        $limiter = new \Lightpack\Utils\Limiter();
        $key = 'mfa_sms_resend_' . $user->id;

        if (!$limiter->attempt($key, $maxAttempts, $intervalSeconds)) {
            throw new RuntimeException("Please wait before requesting a new SMS code.");
        }
    }
}

