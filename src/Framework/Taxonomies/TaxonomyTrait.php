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
     * Get all taxonomy nodes attached to this model using polymorphic many-to-many relationship.
     * Returns a PolymorphicPivot instance for Taxonomy models.
     */
    public function taxonomies()
    {
        return $this->morphToMany(Taxonomy::class, 'taxonomy_models', 'taxonomy_id');
    }

    /**
     * Attach taxonomy nodes to this model.
     */
    public function attachTaxonomies(array $taxonomyIds)
    {
        $this->taxonomies()->attach($taxonomyIds);
    }

    /**
     * Detach taxonomy nodes from this model.
     */
    public function detachTaxonomies(array $taxonomyIds)
    {
        $this->taxonomies()->detach($taxonomyIds);
    }

    /**
     * Sync taxonomy nodes for this model.
     */
    public function syncTaxonomies(array $taxonomyIds)
    {
        $this->taxonomies()->sync($taxonomyIds);
    }

    public function scopeTaxonomies($builder, array $taxonomyIds = [])
    {
        $table = $builder->getTable();
        
        // Only set select if user hasn't already specified columns
        if (empty($builder->columns)) {
            $builder->select($table . '.*');
        }
        
        $builder->join('taxonomy_models AS tx_any', $table . '.id', 'tx_any.morph_id')
            ->where('tx_any.morph_type', $this->table)
            ->whereIn('tx_any.taxonomy_id', $taxonomyIds)
            ->groupBy($table . '.id');
    }
}
