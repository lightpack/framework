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
        return $this->morphToMany(Taxonomy::class, 'taxonomy_morphs', 'taxonomy_id');
    }

    public function scopeTaxonomies($builder, array $taxonomyIds = [])
    {
        $table = $builder->getTable();
        
        // Only set select if user hasn't already specified columns
        if (empty($builder->columns)) {
            $builder->select($table . '.*');
        }
        
        $builder->join('taxonomy_morphs AS tx_any', $table . '.id', 'tx_any.morph_id')
            ->where('tx_any.morph_type', $this->table)
            ->whereIn('tx_any.taxonomy_id', $taxonomyIds)
            ->groupBy($table . '.id');
    }
}
