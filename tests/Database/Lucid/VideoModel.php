<?php

use Lightpack\Database\Lucid\Model;

class VideoModel extends Model
{
    protected $table = 'videos';

    public function comments()
    {
        return $this->morphMany(PolymorphicCommentModel::class, 'commentable_type', 'commentable_id', 'video');
    }
}
