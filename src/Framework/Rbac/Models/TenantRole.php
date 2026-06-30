<?php

namespace Lightpack\Rbac\Models;

use Lightpack\Database\Lucid\TenantModel;

/**
 * Tenant-scoped Role model.
 *
 * When used with RbacTrait on a TenantModel, roles are automatically
 * scoped to the current tenant. No userland configuration required.
 */
class TenantRole extends TenantModel
{
    protected $table = 'roles';

    protected $primaryKey = 'id';

    public $timestamps = true;

    /**
     * Get the permissions relationship for this role.
     */
    public function permissions()
    {
        return $this->pivot(TenantPermission::class, 'role_permission', 'role_id', 'permission_id');
    }
}
