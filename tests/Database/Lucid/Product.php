<?php

require_once 'Owner.php';
require_once 'Option.php';
require_once 'ProductApiTransformer.php';
require_once 'ProductViewTransformer.php';

use \Lightpack\Database\Lucid\Model;

class Product extends Model
{   
    protected $table = 'products';
    protected $transformer = [
        'api' => \ProductApiTransformer::class, 
        'view' => \ProductViewTransformer::class, 
    ];

    public function options()
    {
        return $this->hasMany(Option::class, 'product_id');
    }

    public function owner()
    {
        return $this->hasOne(Owner::class, 'product_id');
    }
}