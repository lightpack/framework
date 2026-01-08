<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\Query\Query;

class PolymorphicPivot extends Pivot
{
    protected $morphType;

    /**
     * @param Model $model The relating model class name.
     * @param Model $baseModel The base model class name.
     * @param string $pivot Name of the pivot table.
     * @param string $associateKey The pivot table column that references the related model (e.g., 'tag_id').
     * @param string $morphType The morph type value (e.g., 'posts').
     * 
     * Note: Polymorphic pivot tables MUST have columns named 'morph_id' and 'morph_type'.
     * This enforced naming keeps things predictable and consistent with morphMany/morphOne/morphTo.
     */
    public function __construct(
        Model $model, 
        Model $baseModel, 
        string $pivotTable,
        string $associateKey,
        string $morphType
    ) {
        // Polymorphic pivot always uses 'morph_id' as foreign key
        parent::__construct($model, $baseModel, $pivotTable, 'morph_id', $associateKey);
        
        $this->morphType = $morphType;
    }

    /**
     * Get a Query instance for the pivot table with morph type filter applied.
     */
    private function pivotQuery(): Query
    {
        $query = new Query($this->pivotTable, $this->getConnection());
        $query->where('morph_type', '=', $this->morphType);
        return $query;
    }

    /**
     * Sync records in the pivot table with polymorphic support.
     */
    public function sync(array $ids, array $attributes = [])
    {
        $this->getConnection()->transaction(function () use ($ids, $attributes) {
            // Get current IDs for this morph type
            $query = $this->pivotQuery();
            $currentIds = $query
                ->where('morph_id', '=', $this->baseModel->getAttribute($this->baseModel->getPrimaryKey()))
                ->select($this->associateKey)
                ->all();

            $currentIds = array_column($currentIds, $this->associateKey);

            // Find IDs to delete and insert
            $idsToDelete = array_diff($currentIds, $ids);
            $idsToInsert = array_values(array_diff($ids, $currentIds));

            // Delete removed IDs
            if ($idsToDelete) {
                $deleteQuery = $this->pivotQuery();
                $deleteQuery->where('morph_id', '=', $this->baseModel->getAttribute($this->baseModel->getPrimaryKey()))
                    ->whereIn($this->associateKey, $idsToDelete);
                foreach ($attributes as $key => $value) {
                    $deleteQuery->where($key, $value);
                }
                $deleteQuery->delete();
            }

            // Insert new IDs
            if ($idsToInsert) {
                $data = array_map(function ($id) use ($attributes) {
                    return array_merge([
                        'morph_id' => $this->baseModel->getAttribute($this->baseModel->getPrimaryKey()),
                        $this->associateKey => $id,
                        'morph_type' => $this->morphType,
                    ], $attributes);
                }, $idsToInsert);

                $insertQuery = new Query($this->pivotTable, $this->getConnection());
                $insertQuery->insert($data);
            }
        });
    }

    /**
     * Attach records to the pivot table with polymorphic support.
     */
    public function attach($ids, array $attributes = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $data = array_map(function ($id) use ($attributes) {
            return array_merge([
                'morph_id' => $this->baseModel->getAttribute($this->baseModel->getPrimaryKey()),
                $this->associateKey => $id,
                'morph_type' => $this->morphType,
            ], $attributes);
        }, $ids);

        if ($data) {
            $query = new Query($this->pivotTable, $this->getConnection());
            $query->insertIgnore($data);
        }
    }

    /**
     * Detach records from the pivot table with polymorphic support.
     */
    public function detach($ids, array $attributes = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $query = $this->pivotQuery();
        $query->where('morph_id', '=', $this->baseModel->getAttribute($this->baseModel->getPrimaryKey()))
            ->whereIn($this->associateKey, $ids);
        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }
        $query->delete();
    }
}
