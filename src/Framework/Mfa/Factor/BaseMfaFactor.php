<?php

namespace Lightpack\Mfa\Factor;

use Lightpack\Auth\Models\AuthUser;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\Mfa\MfaInterface;
use Lightpack\Utils\Otp;
use RuntimeException;

abstract class BaseMfaFactor implements MfaInterface
{
    public function __construct(
        protected Cache $cache,
        protected Config $config,
        protected Otp $otp,
    ) {}

    abstract protected function getType(): string; // 'email', 'sms', etc.
    abstract protected function doSend(AuthUser $user, string $code): void;

    public function send(AuthUser $user): void
    {
        $this->enforceResendRateLimit($user);

        $this->cache->set(
            $this->getCacheKey($user),
            $code = $this->generateCode(),
            $this->config->get('mfa.' . $this->getType() . '.ttl')
        );

        $this->doSend($user, $code);
    }

    public function validate(AuthUser $user, ?string $input): bool
    {
        if(!$input) return false;
        $key = $this->getCacheKey($user);
        $code = $this->cache->get($key);
        if ($code && $input == $code) {
            $this->cache->delete($key);
            return true;
        }
        return false;
    }

    public function getName(): string
    {
        return $this->getType();
    }

    protected function getCacheKey(AuthUser $user): string
    {
        return 'mfa_' . $this->getType() . '_' . $user->id;
    }

    protected function generateCode(): string
    {
        $bypass = $this->config->get('mfa.' . $this->getType() . '.bypass_code');
        if ($bypass) {
            return $bypass;
        }
        return $this->otp
            ->length($this->config->get('mfa.' . $this->getType() . '.code_length', 6))
            ->type($this->config->get('mfa.' . $this->getType() . '.code_type', 'numeric'))
            ->generate();
    }

    protected function enforceResendRateLimit(AuthUser $user): void
    {
        $maxAttempts = $this->config->get('mfa.' . $this->getType() . '.resend_max', 1);
        $intervalSeconds = $this->config->get('mfa.' . $this->getType() . '.resend_interval', 10);
        $limiter = new \Lightpack\Utils\Limiter();
        $key = 'mfa_resend_' . $user->id;
        if ($this->getType() !== 'email') {
            $key = 'mfa_' . $this->getType() . '_resend_' . $user->id;
        }

        if (!$limiter->attempt($key, $maxAttempts, $intervalSeconds)) {
            throw new RuntimeException("Please wait before requesting a new MFA code.");
        }
    }
}
