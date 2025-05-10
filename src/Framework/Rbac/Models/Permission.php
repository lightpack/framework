<?php

namespace Lightpack\Rbac\Models;

use Lightpack\Database\Lucid\Model;

/**
 * Permission model for RBAC.
 */
class Permission extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    public $timestamps = true;
}
