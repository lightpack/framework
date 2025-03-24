<?php

use Lightpack\Database\Transformer;

class ProductApiTransformer extends Transformer 
{
    protected function data($model): array 
    {
        return [
            'name' => $model->name,
            'price' => $model->price,
        ];
    }
}