<?php

namespace Lightpack\Tags;

use Lightpack\Database\Lucid\Model;

class Tag extends Model
{
    protected $table = 'tags';
    protected $primaryKey = 'id';
    protected $timestamps = true;
}
