<?php

use Lightpack\Database\Lucid\Transformer;

class ProductApiTransformer extends Transformer
{
    protected function data(Product $product): array
    {
        return [
            'name' => $product->name,
            'price' => $product->price,
        ];
    }
}
