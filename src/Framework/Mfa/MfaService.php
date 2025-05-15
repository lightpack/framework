<?php
namespace Lightpack\Mfa;

/**
 * MFA Service orchestrates registration and usage of MFA factors.
 */
class MfaService
{
    /** @var MfaInterface[] */
    protected $factors = [];

    /**
     * Register an MFA factor.
     */
    public function registerFactor(MfaInterface $factor): void
    {
        $this->factors[$factor->getName()] = $factor;
    }

    /**
     * Get a registered MFA factor by name.
     */
    public function getFactor(string $name): ?MfaInterface
    {
        return $this->factors[$name] ?? null;
    }

    /**
     * List all registered factor names.
     * @return string[]
     */
    public function getFactorNames(): array
    {
        return array_keys($this->factors);
    }
}
