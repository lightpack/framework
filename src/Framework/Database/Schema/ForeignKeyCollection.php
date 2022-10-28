<?php

namespace Lightpack\Database\Schema;

class ForeignKeyCollection
{
    /**
     * @var array Lightpack\Database\Schema\ForeignKey
     */
    private $keys = [];

    public function add(ForeignKey $key)
    {
        $this->keys[] = $key;
    }

    public function compile()
    {
        $constraints = [];

        foreach ($this->keys as $key) {
            $constraints[] = $key->compile();
        }

        return implode(', ', $constraints);
    }
}
