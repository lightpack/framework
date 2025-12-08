<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\Query\Query;

/**
 * TenantModel - Base class for multi-tenant models
 * 
 * Provides automatic tenant isolation for all CRUD operations 
 * using row-level (column-based) tenancy.
 * 
 * Usage:
 * ```php
 * class Post extends TenantModel
 * {
 *     protected $table = 'posts';
 *     protected $tenantColumn = 'site_id';  // Optional, defaults to 'tenant_id'
 * }
 * 
 * // Set tenant context (in route filter, middleware, etc.)
 * TenantModel::setContext($tenantId);
 * 
 * // All queries are now automatically scoped
 * $posts = Post::query()->all(); // Only current tenant's posts
 * ```
 * 
 * Setting Tenant Context:
 * ```php
 * // Web apps (session-based)
 * TenantModel::setContext(session()->get('tenant.id'));
 * 
 * // API apps (JWT/token-based)
 * TenantModel::setContext(auth()->user()->tenant_id);
 * 
 * // CLI commands
 * TenantModel::setContext($tenant->id);
 * 
 * // Clear context
 * TenantModel::clearContext();
 * ```
 */
class TenantModel extends Model
{
    /**
     * The column name used for tenant identification.
     * Override this in child models if using a different column.
     * 
     * @var string
     */
    protected $tenantColumn = 'tenant_id';

    /**
     * Current tenant context (shared across all tenant models)
     * 
     * @var int|null
     */
    protected static $tenantId = null;

    /**
     * Set the current tenant context.
     * All subsequent queries will be scoped to this tenant.
     * 
     * @param int $tenantId
     * @return void
     */
    public static function setContext(int $tenantId): void
    {
        static::$tenantId = $tenantId;
    }

    /**
     * Get the current tenant context.
     * 
     * @return int|null
     */
    public static function getContext(): ?int
    {
        return static::$tenantId;
    }

    /**
     * Clear the tenant context.
     * Queries will no longer be scoped by tenant.
     * 
     * @return void
     */
    public static function clearContext(): void
    {
        static::$tenantId = null;
    }

    /**
     * Automatically filter all queries by tenant.
     * Applied to: SELECT, UPDATE, DELETE queries.
     * 
     * @param Query $query
     * @return void
     */
    public function globalScope(Query $query)
    {
        $tenantId = static::getContext();

        if ($tenantId !== null) {
            $query->where($this->tenantColumn, $tenantId);
        }
    }

    /**
     * Auto-assign tenant when using save() on new records.
     * 
     * @return void
     */
    protected function beforeSave()
    {
        $tenantId = static::getContext();

        // Only set on INSERT (when primary key is null)
        if (
            $tenantId !== null
            && !$this->hasAttribute($this->tenantColumn)
            && !$this->hasAttribute($this->primaryKey)
        ) {
            $this->setAttribute($this->tenantColumn, $tenantId);
        }
    }

    /**
     * Auto-assign tenant when using insert() directly.
     * CRITICAL: Without this, direct insert() calls bypass tenant isolation!
     * 
     * @return void
     */
    protected function beforeInsert()
    {
        $tenantId = static::getContext();

        if ($tenantId !== null && !$this->hasAttribute($this->tenantColumn)) {
            $this->setAttribute($this->tenantColumn, $tenantId);
        }
    }

    /**
     * Prevent accidental tenant changes on update().
     * 
     * Only enforces if tenant column wasn't explicitly changed by user.
     * This allows intentional tenant transfers when needed.
     * 
     * @return void
     */
    protected function beforeUpdate()
    {
        $tenantId = static::getContext();

        // Only enforce if tenant column wasn't explicitly changed by user
        if ($tenantId !== null && !$this->isDirty($this->tenantColumn)) {
            // Ensure tenant is set (defensive check)
            if (!$this->hasAttribute($this->tenantColumn)) {
                $this->setAttribute($this->tenantColumn, $tenantId);
            }
        }
    }
}
