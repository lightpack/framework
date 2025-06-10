<?php
use Lightpack\Database\Lucid\Model;

class PolymorphicThumbnailModel extends Model
{
    protected $table = 'polymorphic_thumbnails';

    public function thumbnailable()
    {
        return $this->morphTo('thumbnailable_type', 'thumbnailable_id', [
            'post' => PostModel::class,
            'video' => VideoModel::class,
        ]);
    }
}
