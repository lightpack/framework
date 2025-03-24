<?php

use Lightpack\Database\Lucid\Transformer;

class ProductViewTransformer extends Transformer
{
    protected function data($model): array 
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'price' => $model->price,
            'color' => $model->color,
        ];
    }
}