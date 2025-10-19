<?php

use Lightpack\Database\Lucid\Model;

class TimestampCastModel extends Model
{
    protected $table = 'timestamp_cast_models';
    protected $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
