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

    public function getDescendants(): array
    {
        $descendants = [];
        foreach ($this->children()->all() as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getDescendants());
        }
        return $descendants;
    }

    public function getAncestors(): array
    {
        $ancestors = [];
        $parent = $this->parent()->one();
        while ($parent) {
            $ancestors[] = $parent;
            $parent = $parent->parent()->one();
        }
        return array_reverse($ancestors);
    }

    public function getHierarchy(): array
    {
        $node = $this->toArray();
        $children = [];
        foreach ($this->children()->all() as $child) {
            $children[] = $child->getHierarchy();
        }
        if ($children) {
            $node['children'] = $children;
        }
        return $node;
    }
}
