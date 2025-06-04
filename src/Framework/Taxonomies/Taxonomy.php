<?php

namespace Lightpack\Taxonomies;

use Lightpack\Database\Lucid\Model;

/**
 * Taxonomy Model
 * Represents a node in the taxonomy tree (category, tag, menu, etc.).
 */
class Taxonomy extends Model
{
    protected $table = 'taxonomies';
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * Get the parent taxonomy node (if any).
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the child taxonomy nodes.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
