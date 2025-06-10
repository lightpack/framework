<?php

use Lightpack\Database\Lucid\Model;

class PolymorphicThumbnailModel extends Model
{
    protected $table = 'polymorphic_thumbnails';

    public function thumbnailable()
    {
        return $this->morphTo([
            PostModel::class,
            VideoModel::class,
        ]);
    }
}
