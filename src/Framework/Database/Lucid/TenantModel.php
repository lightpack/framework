<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\Query\Query;

/**
 * TenantModel - Base class for multi-tenant models
 * 
 * Provides automatic tenant isolation for all CRUD operations 
 * using row-level (column-based) tenancy.
 * 
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
     * Automatically filter all queries by tenant.
     * Applied to: SELECT, UPDATE, DELETE queries.
     * 
     * @param Query $query
     * @return void
     */
    public function globalScope(Query $query)
    {
        $tenantId = TenantContext::get();

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
        $tenantId = TenantContext::get();

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
        $tenantId = TenantContext::get();

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
        $tenantId = TenantContext::get();

        // Only enforce if tenant column wasn't explicitly changed by user
        if ($tenantId !== null && !$this->isDirty($this->tenantColumn)) {
            // Ensure tenant is set (defensive check)
            if (!$this->hasAttribute($this->tenantColumn)) {
                $this->setAttribute($this->tenantColumn, $tenantId);
            }
        }
    }
}
