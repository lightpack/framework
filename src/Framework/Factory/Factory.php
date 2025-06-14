<?php

namespace Lightpack\Factory;

abstract class Factory
{
    /**
     * @var int|null Number of entities to produce in batch mode.
     */
    protected ?int $batchCount = null;

    /**
     * Return an array representing a single instance of the entity.
     * Concrete factories must implement this.
     */
    abstract protected function template(): array;

    /**
     * Set the number of instances to make().
     * Fluent interface.
     */
    public function times(int $count): static
    {
        $this->batchCount = $count;
        return $this;
    }

    /**
     * Make one or many entities depending on times().
     * Resets count after use.
     */
    public function make(array $overrides = [])
    {
        if ($this->batchCount !== null) {
            $result = $this->items($this->batchCount, $overrides);
            $this->batchCount = null;
            return $result;
        }
        return $this->item($overrides);
    }

    /**
     * Create a single entity array, with optional field overrides.
     */
    protected function item(array $overrides = []): array
    {
        return array_merge($this->template(), $overrides);
    }

    /**
     * Create multiple entity arrays.
     */
    protected function items(int $count, array $overrides = []): array
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $this->item($overrides);
        }
        return $result;
    }
}
