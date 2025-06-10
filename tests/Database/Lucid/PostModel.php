<?php

use Lightpack\Database\Lucid\Model;

class PostModel extends Model
{
    protected $table = 'posts';

    public function comments()
    {
        return $this->morphMany(PolymorphicCommentModel::class, $this->table);
    }

    public function thumbnail()
    {
        return $this->morphOne(PolymorphicThumbnailModel::class, $this->table);
    }
}
