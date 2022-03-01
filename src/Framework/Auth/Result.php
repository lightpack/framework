<?php

namespace Lightpack\Auth;

class Result
{
    /** @var \Lightpack\Auth\Identity */
    protected $identity;

    public function __construct(?Identity $identity = null)
    {
        $this->identity = $identity;
    }

    public function getData(): array
    {
        return $this->identity ? $this->identity->toArray() : [];
    }

    public function isSuccess(): bool
    {
        return null !== $this->identity;
    }

    public function getIdentity(): ?Identity
    {
        return $this->identity;
    }

    public function setIdentity(Identity $identity): self
    {
        $this->identity = $identity;

        return $this;
    }
}