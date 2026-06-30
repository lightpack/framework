<?php

namespace Lightpack\Taxonomies;

use Lightpack\Database\Lucid\Model;

/**
 * Taxonomy Model
 * Represents a node in the taxonomy tree (category, tag, menu, etc.).
 */
class Taxonomy extends Model
{
    use HierarchicalTrait;

    protected $table = 'taxonomies';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $casts = [
        'meta' => 'array',
    ];
}
