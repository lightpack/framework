<?php

namespace Lightpack\Database\Lucid\Attributes;

class AttributeHandler
{
    /**
     * @var \stdClass Model data object
     */
    protected $data;

    /**
     * @var array Attributes to be hidden for serialization or array conversion.
     */
    protected $hidden = [];

    /**
     * @var bool Whether to use timestamps
     */
    protected $timestamps = false;

    public function __construct()
    {
        $this->data = new \stdClass;
    }

    /**
     * Get an attribute value.
     */
    public function get(string $key, $default = null)
    {
        return $this->data->{$key} ?? $default;
    }

    /**
     * Set an attribute value.
     */
    public function set(string $key, $value): void
    {
        $this->data->{$key} = $value;
    }

    /**
     * Check if an attribute exists.
     */
    public function has(string $key): bool
    {
        return property_exists($this->data, $key);
    }

    /**
     * Get all attributes.
     */
    public function all(): \stdClass
    {
        return $this->data;
    }

    /**
     * Set multiple attributes at once.
     */
    public function fill(array $data): void
    {
        $this->data = (object) $data;
    }

    /**
     * Get attributes as array, respecting hidden fields.
     */
    public function toArray(): array
    {
        $data = get_object_vars($this->data);
        return array_filter($data, function ($key) {
            return !in_array($key, $this->hidden);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Set hidden attributes.
     */
    public function setHidden(array $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * Enable/disable timestamps.
     */
    public function setTimestamps(bool $timestamps): void
    {
        $this->timestamps = $timestamps;
    }

    /**
     * Update timestamps when saving/updating.
     */
    public function updateTimestamps(bool $isUpdate = false): void
    {
        if (!$this->timestamps) {
            return;
        }

        if ($isUpdate) {
            $this->data->updated_at = date('Y-m-d H:i:s');
        } else {
            $this->data->created_at = date('Y-m-d H:i:s');
        }
    }
}
