<?php

use Lightpack\Database\Lucid\Transformer;

class ProductViewTransformer extends Transformer
{
    protected function data(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'color' => $product->color,
        ];
    }
}
