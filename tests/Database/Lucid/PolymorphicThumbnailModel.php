<?php

use Lightpack\Database\Lucid\Model;

class PolymorphicThumbnailModel extends Model
{
    protected $table = 'polymorphic_thumbnails';

    public function parent()
    {
        return $this->morphTo([
            PostModel::class,
            VideoModel::class,
        ]);
    }
}
