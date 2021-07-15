<?php

require_once 'Product.php';

use \Lightpack\Database\Lucid\Model;

class Owner extends Model
{   
    protected $table = 'owners';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}