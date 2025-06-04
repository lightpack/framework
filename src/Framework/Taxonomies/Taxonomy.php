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

    protected $casts = [
        'meta' => 'array',
    ];

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
    /**
     * Get the child taxonomy nodes relation, ordered by sort_order then id.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the sibling taxonomy nodes (excluding the current node).
     */
    /**
     * Get the sibling taxonomy nodes (excluding the current node), ordered by sort_order then id.
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
        $query->orderBy('sort_order')->orderBy('id');
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
     * Get the full hierarchical slug for this taxonomy (e.g., 'root/child/grandchild').
     *
     * @param string $separator
     * @return string
     */
    public function fullSlug(string $seperator = '/'): string
    {
        $slugs = array_map(fn($node) => $node->slug, $this->ancestors()->getItems());
        $slugs[] = $this->slug;
        $slugs = array_filter($slugs, fn($slug) => (string)$slug !== '');
        return implode($seperator, $slugs);
    }

    /**
     * Move this taxonomy node (and its subtree) under a new parent.
     *
     * @param int|null $newParentId
     * @return void
     */
    public function moveTo($newParentId): void
    {
        if ($newParentId === $this->id) {
            throw new \InvalidArgumentException("A taxonomy cannot be its own parent.");
        }

        // Check for cycles: ensure new parent is not a descendant of this node
        $descendantIds = $this->descendants()->ids();
        if (in_array($newParentId, $descendantIds, true)) {
            throw new \InvalidArgumentException("Cannot move taxonomy under its own descendant (would create a cycle).");
        }

        $this->parent_id = $newParentId;
        $this->save();
    }

    /**
     * Get a collection of all root taxonomy nodes (parent_id is null).
     *
     * @return Collection
     */
    public static function roots(): Collection
    {
        return self::query()->whereNull('parent_id')->orderBy('sort_order')->orderBy('id')->all();
    }

    /**
     * Reorder multiple taxonomy nodes by sort_order.
     *
     * @param array $idOrderMap [taxonomy_id => sort_order, ...]
     * @return void
     */
    public static function reorder(array $idOrderMap): void
    {
        foreach ($idOrderMap as $id => $order) {
            self::query()->where('id', '=', $id)->update(['sort_order' => $order]);
        }
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
