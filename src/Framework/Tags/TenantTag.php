<?php

namespace Lightpack\Tags;

use Lightpack\Database\Lucid\TenantModel;

/**
 * Tenant-scoped Tag model.
 *
 * When used with TagsTrait on a TenantModel, tags are automatically
 * scoped to the current tenant. No userland configuration required.
 */
class TenantTag extends TenantModel
{
    protected $table = 'tags';

    protected $primaryKey = 'id';

    public $timestamps = true;
}
