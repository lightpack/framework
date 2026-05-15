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

    public function compile(string $context = 'create')
    {
        $constraints = [];

        foreach ($this->keys as $key) {
            $prefix = $context === 'alter' ? 'ADD ' : '';
            $constraints[] = $prefix . $key->compile();
        }

        return implode(', ', $constraints);
    }
}
