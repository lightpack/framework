<?php

namespace Lightpack\Auth;

interface Identity
{
    /**
     * Get the unique identifier for the user.
     *
     * @return null|int|string
     */
    public function getId(): mixed;

    /**
     * Get the API auth token for the user.
     */
    public function getAuthToken(): ?string;

    /**
     * Set the API auth token for the user.
     */
    public function setAuthToken(string $token): void;

    /**
     * Get the remember token for the user.
     */
    public function getRememberToken(): ?string;

    /**
     * Set the remember token for the user.
     */
    public function setRememberToken(string $token): void;
}
