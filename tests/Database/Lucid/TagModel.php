<?php

use Lightpack\Database\Lucid\Model;

class TagModel extends Model
{
    protected $table = 'tags';

    /**
     * Get all posts that have this tag (inverse polymorphic many-to-many)
     */
    public function posts()
    {
        return $this->morphedByMany(PostModel::class, 'tag_morphs', 'tag_id');
    }

    /**
     * Get all videos that have this tag (inverse polymorphic many-to-many)
     */
    public function videos()
    {
        return $this->morphedByMany(VideoModel::class, 'tag_morphs', 'tag_id');
    }
}
