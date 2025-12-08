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
 * ```
 * 
 * Tenant Resolution:
 * The default implementation uses session storage, but you can customize
 * tenant resolution by overriding getTenantId() in your base model:
 * 
 * ```php
 * // For session-based (default):
 * session()->set('tenant.id', $currentTenantId);
 * 
 * // For JWT/API (override in your base model):
 * class ApiTenantModel extends TenantModel
 * {
 *     protected function getTenantId(): ?int
 *     {
 *         return auth()->user()?->tenant_id;
 *     }
 * }
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
     * Get the current tenant ID.
     * 
     * Default implementation uses session storage. Override this method
     * to customize tenant resolution (e.g., from JWT, request header, etc.)
     * 
     * @return int|null
     */
    protected function getTenantId(): ?int
    {
        // Default: Session-based tenant resolution
        try {
            return app('session')->get('tenant.id');
        } catch (\Exception $e) {
            // Session not available (e.g. in CLI context)
            return null;
        }
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
        $tenantId = $this->getTenantId();

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
        $tenantId = $this->getTenantId();

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
        $tenantId = $this->getTenantId();

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
        $tenantId = $this->getTenantId();

        // Only enforce if tenant column wasn't explicitly changed by user
        if ($tenantId !== null && !$this->isDirty($this->tenantColumn)) {
            // Ensure tenant is set (defensive check)
            if (!$this->hasAttribute($this->tenantColumn)) {
                $this->setAttribute($this->tenantColumn, $tenantId);
            }
        }
    }
}
