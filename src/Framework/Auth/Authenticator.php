<?php

namespace Lightpack\Auth;

abstract class Authenticator
{
    /** @var \Lightpack\Auth\IdentifierInterface */
    protected $identifier;

    /** @var array */
    protected $config;

    public function __construct(IdentifierInterface $identifier, array $config)
    {
        $this->identifier = $identifier;
        $this->config = $config;
    }

    public function setIdentifier(IdentifierInterface $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): IdentifierInterface
    {
        return $this->identifier;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    abstract public function verify(): ?IdentityInterface;
}
