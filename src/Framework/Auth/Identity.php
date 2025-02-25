<?php

namespace Lightpack\Auth;

use Lightpack\Auth\Models\AccessToken;

interface Identity
{
    /**
     * Get the unique identifier for the user.
     *
     * @return null|int|string
     */
    public function getId(): mixed;

    /**
     * Get the remember token for the user.
     */
    public function getRememberToken(): ?string;

    /**
     * Set the remember token for the user.
     */
    public function setRememberToken(string $token): void;

    /**
     * Retrieve access token for the user.
     */
    public function accessTokens();

    /**
     * Create an access token for the user.
     */
    public function createToken(string $name, array $abilities = ['*'], ?string $expiresAt = null): AccessToken;

    /**
     * Delete an access token assigned to the user.
     */
    public function deleteTokens(?string $tokenId = '');
}
