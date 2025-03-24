<?php

use Lightpack\Database\Transformer;

class ProjectTransformer extends Transformer 
{
    protected function data($model): array 
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
        ];
    }
}