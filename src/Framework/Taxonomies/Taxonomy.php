<?php

namespace Lightpack\Taxonomies;

use Lightpack\Database\Lucid\Collection;
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
     * Get the parent taxonomy node (if any) relation.
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the child taxonomy nodes relation.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get the sibling taxonomy nodes (excluding the current node).
     */
    public function siblings(): Collection
    {
        $query = self::query();

        if ($this->parent_id === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', '=', $this->parent_id);
        }

        $query->where('id', '!=', $this->id);
        return $query->all();
    }

    public function ancestors(): Collection
    {
        $ancestors = [];
        $parent = $this->parent()->one();

        while ($parent) {
            $ancestors[] = $parent;
            $parent = $parent->parent()->one();
        }
        
        return new Collection(array_reverse($ancestors));
    }

    public function descendants(): Collection
    {
        $descendants = [];
        foreach ($this->children()->all() as $child) {
            $descendants[] = $child;
            foreach ($child->descendants() as $descendant) {
                $descendants[] = $descendant;
            }
        }
        return new Collection($descendants);
    }

    public function tree(): array
    {
        $node = $this->toArray();
        $children = [];
        foreach ($this->children()->all() as $child) {
            $children[] = $child->tree();
        }
        if ($children) {
            $node['children'] = $children;
        }
        return $node;
    }

    /**
     * Get a collection of all root taxonomy nodes (parent_id is null).
     *
     * @return Collection
     */
    public static function roots(): Collection
    {
        return self::query()->whereNull('parent_id')->all();
    }

    /**
     * Get the full taxonomy forest as an array of nested trees (one per root node).
     * Each tree is a nested array as produced by tree().
     *
     * @return array
     */
    public static function forest(): array
    {
        $roots = self::roots();
        return array_map(fn($root) => $root->tree(), $roots->getItems());
    }
}
