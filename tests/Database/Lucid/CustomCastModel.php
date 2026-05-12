<?php

use Lightpack\Database\Lucid\Casts\UpperCaseCast;
use Lightpack\Database\Lucid\Model;

class CustomCastModel extends Model
{
    protected $table = 'cast_models';
    protected $timestamps = false;

    protected $casts = [
        'string_col' => UpperCaseCast::class,
        'integer_col' => 'int',
    ];
}
