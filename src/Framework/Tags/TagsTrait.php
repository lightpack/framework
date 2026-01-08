<?php

namespace Lightpack\Tags;

use Lightpack\Tags\Tag;

trait TagsTrait
{
    /**
     * Get all tags for this model using polymorphic many-to-many relationship.
     * Returns a PolymorphicPivot instance for Tag models.
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'tag_models', 'tag_id');
    }

    /**
     * Attach tags to this model.
     */
    public function attachTags(array $tagIds)
    {
        $this->tags()->attach($tagIds);
    }

    /**
     * Detach tags from this model.
     */
    public function detachTags(array $tagIds)
    {
        $this->tags()->detach($tagIds);
    }

    /**
     * Sync tags for this model.
     */
    public function syncTags(array $tagIds)
    {
        $this->tags()->sync($tagIds);
    }

    public function scopeTags($builder, array $tagIds = [])
    {
        $table = $builder->getTable();
        
        // Only set select if user hasn't already specified columns
        if (empty($builder->columns)) {
            $builder->select($table . '.*');
        }
        
        $builder->join('tag_models AS tg_any', $table . '.id', 'tg_any.morph_id')
            ->where('tg_any.morph_type', $this->table)
            ->whereIn('tg_any.tag_id', $tagIds)
            ->groupBy($table . '.id');
    }
}
