<?php

use Lightpack\Database\Lucid\Model;

class PostModel extends Model
{
    protected $table = 'posts';

    public function comments()
    {
        return $this->morphMany(PolymorphicCommentModel::class);
    }

    public function thumbnail()
    {
        return $this->morphOne(PolymorphicThumbnailModel::class);
    }

    public function tags()
    {
        return $this->morphToMany(TagModel::class, 'tag_morphs', 'tag_id');
    }
}
