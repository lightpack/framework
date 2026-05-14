<?php

namespace Lightpack\Mfa;

/**
 * Trait to add MFA convenience methods to a model (e.g., User).
 * Usage: use Lightpack\Mfa\MfaTrait;
 */
trait MfaTrait
{
    /**
     * Get the user's configured MFA factor, or fallback to default.
     * @return MfaInterface|null
     */
    public function getMfaFactor()
    {
        $manager = app('mfa.manager');
        $factor = $this->mfa_method ?? config('mfa.default', 'null');

        return $manager->driver($factor);
    }

    /**
     * Send MFA challenge to the user.
     */
    public function sendMfa()
    {
        $factor = $this->getMfaFactor();
        if ($factor) {
            $factor->send($this);
        }
    }

    /**
     * Validate the user's MFA input.
     * @param mixed $input
     * @return bool
     */
    public function validateMfa($input)
    {
        $factor = $this->getMfaFactor();

        return $factor ? $factor->validate($this, $input) : false;
    }
}
