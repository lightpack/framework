<?php

use Lightpack\Database\Lucid\Model;

class PostModel extends Model
{
    protected $table = 'posts';

    public function comments()
    {
        return $this->morphMany(PolymorphicCommentModel::class, 'post');
    }

    public function thumbnail()
    {
        return $this->morphOne(PolymorphicThumbnailModel::class, 'post');
    }
}
