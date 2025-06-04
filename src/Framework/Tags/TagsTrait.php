<?php

namespace Lightpack\Tags;

use Lightpack\Tags\Tag;

trait TagsTrait
{
    public function tags()
    {
        return $this->pivot(
            Tag::class,
            'tag_models',
            'model_id',
            'tag_id',
        )->where('tag_models.model_type', $this->table);
    }

    public function attachTags(array $tagIds)
    {
        $this->tags()->attach($tagIds, ['model_type' => $this->table]);
    }

    public function detachTags(array $tagIds)
    {
        $this->tags()->detach($tagIds, ['model_type' => $this->table]);
    }

    public function syncTags(array $tagIds)
    {
        $this->tags()->sync($tagIds, ['model_type' => $this->table]);
    }

    public function scopeTags($builder, array $tagIds = [])
    {
        $builder->join('tag_models AS tg_any', $builder->getTable() . '.id', 'tg_any.model_id')
            ->where('tg_any.model_type', $this->table)
            ->whereIn('tg_any.tag_id', $tagIds);
    }
}
