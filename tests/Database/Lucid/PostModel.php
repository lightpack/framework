<?php

use Lightpack\Database\Lucid\Model;

class PostModel extends Model
{
    protected $table = 'posts';

    public function comments()
    {
        return $this->morphMany(PolymorphicCommentModel::class, 'commentable_type', 'commentable_id', 'post');
    }
}
