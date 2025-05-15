<?php
namespace Lightpack\Mfa;

use Lightpack\Auth\Models\AuthUser;

/**
 * Interface for MFA factors (email, sms, totp, etc).
 */
interface MfaInterface
{
    /**
     * Send an MFA challenge to the user (e.g., email, sms, etc).
     * @param object $user User model or object with at least 'id' and 'email' properties
     */
    public function send(AuthUser $user): void;

    /**
     * Validate the user's MFA input (e.g., code).
     * @param object $user
     * @param ?string $input
     * @return bool
     */
    public function validate(AuthUser $user, ?string $input): bool;

    /**
     * Return the unique name for this factor (e.g., 'email', 'sms').
     * @return string
     */
    public function getName(): string;
}
