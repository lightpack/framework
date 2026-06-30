<?php

namespace Lightpack\Rbac\Models;

use Lightpack\Database\Lucid\TenantModel;

/**
 * Tenant-scoped Permission model.
 *
 * When used with RbacTrait on a TenantModel, permissions are automatically
 * scoped to the current tenant. No userland configuration required.
 */
class TenantPermission extends TenantModel
{
    protected $table = 'permissions';

    protected $primaryKey = 'id';

    public $timestamps = true;
}
