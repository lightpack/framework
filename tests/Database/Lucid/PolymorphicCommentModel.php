<?php

use Lightpack\Database\Lucid\Model;

class PolymorphicCommentModel extends Model
{
    protected $table = 'polymorphic_comments';

    // Defines the inverse polymorphic relation
    public function commentable()
    {
        return $this->morphTo([
            'posts' => PostModel::class,
            'videos' => VideoModel::class,
        ]);
    }
}
