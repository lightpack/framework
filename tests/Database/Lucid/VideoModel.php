<?php

use Lightpack\Database\Lucid\Model;

class VideoModel extends Model
{
    protected $table = 'videos';

    public function comments()
    {
        return $this->morphMany(PolymorphicCommentModel::class, 'video');
    }

    public function thumbnail()
    {
        return $this->morphOne(PolymorphicThumbnailModel::class, 'video');
    }
}
