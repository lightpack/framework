<?php

use Lightpack\Database\Lucid\Model;

class PolymorphicCommentModel extends Model
{
    protected $table = 'polymorphic_comments';

    // Defines the inverse polymorphic relation
    public function commentable()
    {
        return $this->morphTo('morph_type', 'morph_id', [
            'post' => PostModel::class,
            'video' => VideoModel::class,
        ]);
    }
}
