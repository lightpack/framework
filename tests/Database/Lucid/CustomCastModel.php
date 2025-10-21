<?php

use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Lucid\Casts\UpperCaseCast;

class CustomCastModel extends Model
{
    protected $table = 'cast_models';
    protected $timestamps = false;

    protected $casts = [
        'string_col' => UpperCaseCast::class,
        'integer_col' => 'int',
    ];
}
