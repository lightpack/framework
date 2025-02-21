<?php

declare(strict_types=1);

namespace Lightpack\Validation\Traits;

trait ValidationMessageTrait
{
    protected string $message;

    public function getMessage(): string 
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
