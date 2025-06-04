<?php

namespace Lightpack\Taxonomies;

/**
 * Trait TaxonomyTrait
 * Provides methods to attach, detach, sync, and query taxonomies for any model.
 * Follows Lightpack's explicit, minimal, and discoverable patterns, similar to TagsTrait.
 */
trait TaxonomyTrait
{
    /**
     * Get all taxonomy nodes (of a given type) attached to this model.
     */
    public function taxonomies()
    {
        return $this->pivot(
            Taxonomy::class,
            'taxonomy_models',
            'model_id',
            'taxonomy_id'
        )->where('taxonomy_models.model_type', $this->table);
    }

    /**
     * Attach taxonomy nodes to this model.
     * @param array $taxonomyIds
     */
    public function attachTaxonomies(array $taxonomyIds)
    {
        // Optionally filter/validate taxonomy ids by type
        $this->taxonomies()->attach($taxonomyIds, [
            'model_type' => $this->table
        ]);
    }

    /**
     * Detach taxonomy nodes from this model.
     * @param array $taxonomyIds
     */
    public function detachTaxonomies(array $taxonomyIds)
    {
        $this->taxonomies()->detach($taxonomyIds, [
            'model_type' => $this->table
        ]);
    }

    /**
     * Sync taxonomy nodes for this model.
     * @param array $taxonomyIds
     */
    public function syncTaxonomies(array $taxonomyIds)
    {
        $this->taxonomies()->sync($taxonomyIds, [
            'model_type' => $this->table
        ]);
    }
}
