<?php
namespace Lightpack\Mfa;

/**
 * Interface for MFA factors (email, sms, totp, etc).
 */
interface MfaInterface
{
    /**
     * Send an MFA challenge to the user (e.g., email, sms, etc).
     * @param object $user User model or object with at least 'id' and 'email' properties
     */
    public function send($user): void;

    /**
     * Validate the user's MFA input (e.g., code).
     * @param object $user
     * @param mixed $input
     * @return bool
     */
    public function validate($user, $input): bool;

    /**
     * Return the unique name for this factor (e.g., 'email', 'sms').
     * @return string
     */
    public function getName(): string;
}
