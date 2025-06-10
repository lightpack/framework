<?php
use Lightpack\Database\Lucid\Model;

class PolymorphicThumbnailModel extends Model
{
    protected $table = 'polymorphic_thumbnails';

    public function thumbnailable()
    {
        return $this->morphTo([
            'posts' => PostModel::class,
            'videos' => VideoModel::class,
        ]);
    }
}
