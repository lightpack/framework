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

    /**
     * @var array Track modified attributes
     */
    protected array $dirty = [];

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

        // Track modification only if value actually changes
        if (!isset($this->data->{$key}) || $this->data->{$key} !== $value) {
            $this->dirty[$key] = true;
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
        unset($this->dirty[$key]); // Clear modification flag for raw sets
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
        $this->clearDirty(); // Clear modified state after database load
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
     * Values are already in database format because set() applies uncasting.
     * This method only needs to handle DateTime objects and arrays that might exist.
     */
    public function toDatabaseArray(): array
    {
        $data = (array) $this->data;
        $result = [];
        
        foreach ($data as $key => $value) {
            // Skip eager-loaded relations (Model or Collection objects)
            if ($value instanceof Model || $value instanceof Collection) {
                continue;
            }
            
            $castType = $this->getCastType($key);
            
            // Handle DateTime objects that might not have been uncast
            if ($value instanceof \DateTimeInterface) {
                if ($castType) {
                    $result[$key] = $this->castHandler->uncast($value, $castType);
                } else {
                    // Default to datetime format if no cast specified
                    $result[$key] = $value->format('Y-m-d H:i:s');
                }
            } 
            // Handle arrays that need to be uncast to JSON
            elseif (is_array($value) && $castType === 'array') {
                $result[$key] = $this->castHandler->uncast($value, $castType);
            } 
            else {
                $result[$key] = $value;
            }
        }
        
        return $result;
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
     * Uses set() method to respect casting if timestamps are in $casts array.
     */
    public function updateTimestamps(bool $updating = true): void
    {
        if (!$this->timestamps) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        if ($updating) {
            $this->set('updated_at', $now);
            return;
        }

        $this->set('created_at', $now);
    }

    /**
     * Get attributes in dirty state.
     */
    public function getDirty(): array
    {
        return array_keys($this->dirty);
    }

    /**
     * Check if model or specific attributes are in dirty state.
     */
    public function isDirty(?string $key = null): bool
    {
        if ($key === null) {
            return !empty($this->dirty);
        }

        return isset($this->dirty[$key]);
    }

    /**
     * Clear modified attributes tracking.
     */
    public function clearDirty(): void
    {
        $this->dirty = [];
    }
}
