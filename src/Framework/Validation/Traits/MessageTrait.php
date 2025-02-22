<?php

declare(strict_types=1);

namespace Lightpack\Validation\Traits;

trait ValidationMessageTrait
{
    /**
     * The validation error message
     */
    private string $message = 'Validation failed';

    /**
     * Get the validation error message
     */
    public function getMessage(): string 
    {
        return $this->message;
    }

    /**
     * Set a custom validation error message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
