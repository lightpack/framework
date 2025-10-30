<?php

class ProductController
{
    // Parameter name must match route parameter name for binding to work
    public function show(Product $id)
    {
        return $id;
    }

    public function showWithScalar(Product $id, $extra)
    {
        return ['product' => $id, 'extra' => $extra];
    }

    // Parameter names must match route parameter names
    public function showMultiple(Product $id, User $user_id)
    {
        return ['product' => $id, 'user' => $user_id];
    }

    public function showByName(Product $name)
    {
        return $name;
    }
}
