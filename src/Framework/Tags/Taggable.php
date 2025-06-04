<?php

namespace Lightpack\Tags;

use Lightpack\Tags\Tag;

trait Taggable
{
    public function tags()
    {
        // Return a pivot relation for tags, filtered by taggable_type
        return $this->pivot(
            Tag::class,
            'taggables',
            'taggable_id',
            'tag_id',
        );
    }

    public function scopeTags($builder, array $tagIds = [])
    {
        $builder->join('taggables AS tg_any', $builder->getTable() . '.id', 'tg_any.taggable_id')
            ->whereIn('tg_any.tag_id', $tagIds);
    }
}
