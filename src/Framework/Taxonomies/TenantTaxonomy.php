<?php

namespace Lightpack\Taxonomies;

use Lightpack\Database\Lucid\TenantModel;

/**
 * Tenant-scoped Taxonomy model.
 *
 * When used with TaxonomyTrait on a TenantModel, taxonomies are automatically
 * scoped to the current tenant. No userland configuration required.
 */
class TenantTaxonomy extends TenantModel
{
    use HierarchicalTrait;

    protected $table = 'taxonomies';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $casts = [
        'meta' => 'array',
    ];
}
