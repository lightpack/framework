<?php

use Lightpack\Database\Lucid\Model;

class CastModel extends Model
{
    protected $table = 'cast_models';

    protected $casts = [
        'string_col' => 'string',
        'integer_col' => 'integer',
        'float_col' => 'float',
        'boolean_col' => 'boolean',
        'json_col' => 'json',
        'date_col' => 'date',
        'datetime_col' => 'datetime',
        'timestamp_col' => 'timestamp',
    ];
}
