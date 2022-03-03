<?php

namespace Lightpack\Auth;

abstract class AbstractAuthenticator
{
    /** @var \Lightpack\Auth\Identifier */
    protected $identifier;

    /** @var array */
    protected $config;

    public function __construct(Identifier $identifier, array $config)
    {
        $this->identifier = $identifier;
        $this->config = $config;
    }

    public function setIdentifier(Identifier $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): Identifier
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

    abstract public function verify(): Result;
}