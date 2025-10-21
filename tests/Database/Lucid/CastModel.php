<?php

use Lightpack\Database\Lucid\Model;

class CastModel extends Model
{
    protected $table = 'cast_models';

    protected $casts = [
        'string_col' => 'string',
        'integer_col' => 'int',
        'float_col' => 'float',
        'boolean_col' => 'bool',
        'json_col' => 'array',
        'date_col' => 'date',
        'datetime_col' => 'datetime',
        'timestamp_col' => 'timestamp',
    ];

    public function options()
    {
        return $this->pivot(self::class, 'cast_model_relations', 'parent_id', 'child_id');
    }
}
