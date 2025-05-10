<?php

namespace Lightpack\Framework\Rbac\Models;

use Lightpack\Database\Lucid\Model;

/**
 * Role model for RBAC.
 */
class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * Get the permissions relationship for this role.
     *
     * @return \Lightpack\Database\Lucid\Pivot
     */
    public function permissions()
    {
        return $this->pivot(Permission::class, 'role_permission', 'role_id', 'permission_id');
    }
}
