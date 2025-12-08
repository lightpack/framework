<?php

namespace Lightpack\Database\Lucid;

/**
 * TenantContext - Manages the current tenant context for multi-tenant applications.
 * 
 * Provides a centralized place to set/get/clear the active tenant scope.
 * This context is used by TenantModel to automatically filter queries.
 * 
 */
class TenantContext
{
    /**
     * Current tenant ID
     * 
     * @var int|null
     */
    protected static $tenantId = null;

    /**
     * Set the current tenant context.
     * All subsequent TenantModel queries will be scoped to this tenant.
     * 
     * @param int $tenantId
     * @return void
     */
    public static function set(int $tenantId): void
    {
        static::$tenantId = $tenantId;
    }

    /**
     * Get the current tenant context.
     * 
     * @return int|null
     */
    public static function get(): ?int
    {
        return static::$tenantId;
    }

    /**
     * Clear the tenant context.
     * Queries will no longer be scoped by tenant.
     * 
     * @return void
     */
    public static function clear(): void
    {
        static::$tenantId = null;
    }

    /**
     * Check if tenant context is currently set.
     * 
     * @return bool
     */
    public static function has(): bool
    {
        return static::$tenantId !== null;
    }
}
