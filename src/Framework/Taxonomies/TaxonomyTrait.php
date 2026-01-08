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
     * Get all taxonomy nodes attached to this model.
     * Returns a Builder instance for Taxonomy models.
     */
    public function taxonomies()
    {
        return Taxonomy::query()
            ->join('taxonomy_models', 'taxonomies.id', 'taxonomy_models.taxonomy_id')
            ->where('taxonomy_models.model_id', '=', $this->{$this->getPrimaryKey()})
            ->where('taxonomy_models.model_type', '=', $this->table);
    }

    /**
     * Attach taxonomy nodes to this model.
     */
    public function attachTaxonomies(array $taxonomyIds)
    {
        $data = array_map(function($taxonomyId) {
            return [
                'taxonomy_id' => $taxonomyId,
                'model_id' => $this->{$this->getPrimaryKey()},
                'model_type' => $this->table,
            ];
        }, $taxonomyIds);

        if ($data) {
            $this->getConnection()
                ->table('taxonomy_models')
                ->insertIgnore($data);
        }
    }

    /**
     * Detach taxonomy nodes from this model.
     */
    public function detachTaxonomies(array $taxonomyIds)
    {
        $this->getConnection()
            ->table('taxonomy_models')
            ->where('model_id', '=', $this->{$this->getPrimaryKey()})
            ->where('model_type', '=', $this->table)
            ->whereIn('taxonomy_id', $taxonomyIds)
            ->delete();
    }

    /**
     * Sync taxonomy nodes for this model.
     */
    public function syncTaxonomies(array $taxonomyIds)
    {
        $this->getConnection()->transaction(function() use ($taxonomyIds) {
            // Get current taxonomy IDs
            $currentIds = $this->getConnection()
                ->table('taxonomy_models')
                ->where('model_id', '=', $this->{$this->getPrimaryKey()})
                ->where('model_type', '=', $this->table)
                ->select('taxonomy_id')
                ->all('taxonomy_id');
            
            $currentIds = array_column($currentIds, 'taxonomy_id');
            
            // Find IDs to delete and insert
            $idsToDelete = array_diff($currentIds, $taxonomyIds);
            $idsToInsert = array_diff($taxonomyIds, $currentIds);
            
            // Delete removed taxonomies
            if ($idsToDelete) {
                $this->detachTaxonomies($idsToDelete);
            }
            
            // Insert new taxonomies
            if ($idsToInsert) {
                $this->attachTaxonomies($idsToInsert);
            }
        });
    }

    public function scopeTaxonomies($builder, array $taxonomyIds = [])
    {
        $table = $builder->getTable();
        
        // Only set select if user hasn't already specified columns
        if (empty($builder->columns)) {
            $builder->select($table . '.*');
        }
        
        $builder->join('taxonomy_models AS tx_any', $table . '.id', 'tx_any.model_id')
            ->where('tx_any.model_type', $this->table)
            ->whereIn('tx_any.taxonomy_id', $taxonomyIds)
            ->groupBy($table . '.id');
    }
}
