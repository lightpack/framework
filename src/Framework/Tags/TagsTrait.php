<?php

namespace Lightpack\Tags;

use Lightpack\Database\Lucid\TenantModel;

trait TagsTrait
{
    /**
     * Get the tag model class used by this trait.
     *
     * When the parent model extends TenantModel, TenantTag is used
     * automatically for tenant-scoped tags. Override this method
     * if you need a custom tag model.
     */
    protected function getTagModel(): string
    {
        if ($this instanceof TenantModel) {
            return TenantTag::class;
        }

        return Tag::class;
    }

    /**
     * Get all tags for this model using polymorphic many-to-many relationship.
     * Returns a PolymorphicPivot instance for Tag models.
     */
    public function tags()
    {
        return $this->morphToMany($this->getTagModel(), 'tag_morphs', 'tag_id');
    }

    public function scopeTags($builder, array $tagIds = [])
    {
        $table = $builder->getTable();

        // Only set select if user hasn't already specified columns
        if (empty($builder->columns)) {
            $builder->select($table . '.*');
        }

        $builder->join('tag_morphs AS tg_any', $table . '.id', 'tg_any.morph_id')
            ->where('tg_any.morph_type', $this->table)
            ->whereIn('tg_any.tag_id', $tagIds)
            ->groupBy($table . '.id');
    }
}
