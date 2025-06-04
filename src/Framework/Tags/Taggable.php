<?php

namespace Lightpack\Tags;

use Lightpack\Tags\Tag;

trait Taggable
{
    public function tags()
    {
        // The relation is still filtered by taggable_type
        return $this->pivot(
            Tag::class,
            'taggables',
            'taggable_id',
            'tag_id',
        )->where('taggables.taggable_type', $this->table);
    }

    public function attachTags(array $tagIds)
    {
        $this->tags()->attach($tagIds, ['taggable_type' => $this->table]);
    }

    public function detachTags(array $tagIds)
    {
        $this->tags()->detach($tagIds, ['taggable_type' => $this->table]);
    }

    public function syncTags(array $tagIds)
    {
        $this->tags()->sync($tagIds, ['taggable_type' => $this->table]);
    }

    public function scopeTags($builder, array $tagIds = [])
    {
        $builder->join('taggables AS tg_any', $builder->getTable() . '.id', 'tg_any.taggable_id')
            ->where('tg_any.taggable_type', $this->table)
            ->whereIn('tg_any.tag_id', $tagIds);
    }
}
