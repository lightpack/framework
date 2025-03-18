<?php

namespace Lightpack\Database\Lucid;

class AttributeHandler
{
    /**
     * @var \stdClass Attributes container
     */
    protected $data;

    /**
     * @var array Attributes to be hidden
     */
    protected $hidden = [];

    /**
     * @var bool Enable timestamps
     */
    protected $timestamps = false;

    /**
     * @var array Cast definitions
     */
    protected array $casts = [];

    /**
     * @var CastHandler
     */
    protected CastHandler $castHandler;

    public function __construct()
    {
        $this->data = new \stdClass;
        $this->castHandler = new CastHandler();
    }

    /**
     * Get attribute with casting.
     */
    public function get(string $key, $default = null)
    {
        if (!isset($this->data->{$key})) {
            return $default;
        }

        $value = $this->data->{$key};
        
        // Return null as is
        if ($value === null) {
            return null;
        }
        
        if ($castType = $this->getCastType($key)) {
            return $this->castHandler->cast($value, $castType);
        }

        return $value;
    }

    /**
     * Set attribute with casting.
     */
    public function set(string $key, $value): void
    {
        if ($value !== null && $castType = $this->getCastType($key)) {
            $value = $this->castHandler->uncast($value, $castType);
        }

        $this->data->{$key} = $value;
    }

    /**
     * Set raw attribute from database without casting.
     */
    public function setRaw(string $key, $value): void
    {
        if ($value !== null && $castType = $this->getCastType($key)) {
            $value = $this->castHandler->cast($value, $castType);
        }

        $this->data->{$key} = $value;
    }

    /**
     * Check if attribute exists.
     */
    public function has(string $key): bool
    {
        return isset($this->data->{$key});
    }

    /**
     * Fill attributes.
     */
    public function fill(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Fill attributes from database.
     */
    public function fillRaw(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->setRaw($key, $value);
        }
    }

    /**
     * Get all attributes.
     */
    public function all(): \stdClass
    {
        return $this->data;
    }

    /**
     * Get attributes as array, respecting hidden fields.
     */
    public function toArray(): array
    {
        $data = (array) $this->data;
        return array_diff_key($data, array_flip($this->hidden));
    }

    /**
     * Get all attributes as array for database operations.
     */
    public function toDatabaseArray(): array
    {
        return (array) $this->data;
    }

    /**
     * Set hidden attributes.
     */
    public function setHidden(array $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * Set timestamps flag.
     */
    public function setTimestamps(bool $timestamps): void
    {
        $this->timestamps = $timestamps;
    }

    /**
     * Set cast definitions.
     */
    public function setCasts(array $casts): void
    {
        $this->casts = $casts;
    }

    /**
     * Get cast type for attribute.
     */
    protected function getCastType(string $key): ?string
    {
        return $this->casts[$key] ?? null;
    }

    /**
     * Update timestamps.
     */
    public function updateTimestamps(bool $updating = true): void
    {
        if (!$this->timestamps) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        if ($updating) {
            $this->data->updated_at = $now;
            return;
        }

        $this->data->created_at = $now;
    }
}
