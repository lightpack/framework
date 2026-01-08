<?php

namespace Lightpack\Tags;

use Lightpack\Tags\Tag;

trait TagsTrait
{
    /**
     * Get all tags for this model.
     * Returns a Builder instance for Tag models.
     */
    public function tags()
    {
        return Tag::query()
            ->join('tag_models', 'tags.id', 'tag_models.tag_id')
            ->where('tag_models.model_id', '=', $this->{$this->getPrimaryKey()})
            ->where('tag_models.model_type', '=', $this->table);
    }

    /**
     * Attach tags to this model.
     */
    public function attachTags(array $tagIds)
    {
        $data = array_map(function($tagId) {
            return [
                'tag_id' => $tagId,
                'model_id' => $this->{$this->getPrimaryKey()},
                'model_type' => $this->table,
            ];
        }, $tagIds);

        if ($data) {
            $this->getConnection()
                ->table('tag_models')
                ->insertIgnore($data);
        }
    }

    /**
     * Detach tags from this model.
     */
    public function detachTags(array $tagIds)
    {
        $this->getConnection()
            ->table('tag_models')
            ->where('model_id', '=', $this->{$this->getPrimaryKey()})
            ->where('model_type', '=', $this->table)
            ->whereIn('tag_id', $tagIds)
            ->delete();
    }

    /**
     * Sync tags for this model.
     */
    public function syncTags(array $tagIds)
    {
        $this->getConnection()->transaction(function() use ($tagIds) {
            // Get current tag IDs
            $currentIds = $this->getConnection()
                ->table('tag_models')
                ->where('model_id', '=', $this->{$this->getPrimaryKey()})
                ->where('model_type', '=', $this->table)
                ->select('tag_id')
                ->all('tag_id');
            
            $currentIds = array_column($currentIds, 'tag_id');
            
            // Find IDs to delete and insert
            $idsToDelete = array_diff($currentIds, $tagIds);
            $idsToInsert = array_diff($tagIds, $currentIds);
            
            // Delete removed tags
            if ($idsToDelete) {
                $this->detachTags($idsToDelete);
            }
            
            // Insert new tags
            if ($idsToInsert) {
                $this->attachTags($idsToInsert);
            }
        });
    }

    public function scopeTags($builder, array $tagIds = [])
    {
        $table = $builder->getTable();
        
        // Only set select if user hasn't already specified columns
        if (empty($builder->columns)) {
            $builder->select($table . '.*');
        }
        
        $builder->join('tag_models AS tg_any', $table . '.id', 'tg_any.model_id')
            ->where('tg_any.model_type', $this->table)
            ->whereIn('tg_any.tag_id', $tagIds)
            ->groupBy($table . '.id');
    }
}
