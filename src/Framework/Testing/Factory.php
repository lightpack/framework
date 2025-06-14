<?php

namespace Lightpack\Testing;

abstract class Factory
{
    /**
     * Return an array representing a single instance of the entity.
     * Concrete factories must implement this.
     */
    abstract protected function definition(): array;

    /**
     * Create a single entity array, with optional field overrides.
     */
    public function make(array $overrides = []): array
    {
        return array_merge($this->definition(), $overrides);
    }

    /**
     * Create multiple entity arrays.
     */
    public function many(int $count, array $overrides = []): array
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $this->make($overrides);
        }
        return $result;
    }
}
