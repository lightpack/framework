<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\Query\Query;

class Pivot extends Builder
{
    private $baseModel;
    private $pivotTable;
    private $foreignKey;
    private $associateKey;

    /**
     * @param Model $model The relating model class name.
     * @param Model $baseModel The base model class name.
     * @param string $pivot Name of the pivot table.
     * @param string $foreignKey Foreign key of the base model.
     * @param string $associateKey Associate key of the relating model.
     * 
     */
    public function __construct(Model $model, Model $baseModel, string $pivotTable, string $foreignKey, string $associateKey)
    {
        $this->baseModel = $baseModel;
        $this->pivotTable = $pivotTable;
        $this->foreignKey = $foreignKey;
        $this->associateKey = $associateKey;

        parent::__construct($model);
    }

    /**
     * This method will sync records in the pivot table. It will delete 
     * records that are not in the array and insert new records.
     */
    /**
     * Sync records in the pivot table, supporting extra columns.
     * @param array $ids IDs to sync.
     * @param array $attributes Extra columns to add to each row (optional).
     */
    public function sync(array $ids, array $attributes = [])
    {
        $this->getConnection()->transaction(function () use ($ids, $attributes) {
            $query = new Query($this->pivotTable, $this->getConnection());

            // Get current IDs
            $currentIds = $query->where($this->foreignKey, '=', $this->baseModel->getAttribute($this->baseModel->getPrimaryKey()))
                ->select($this->associateKey)
                ->all($this->associateKey);

            $currentIds = array_column($currentIds, $this->associateKey);

            // Find IDs to delete (in current but not in new)
            $idsToDelete = array_diff($currentIds, $ids);

            // Find IDs to insert (in new but not in current)
            $idsToInsert = array_values(array_diff($ids, $currentIds));

            // Delete removed IDs
            if ($idsToDelete) {
                $query->where($this->foreignKey, '=', $this->baseModel->getAttribute($this->baseModel->getPrimaryKey()))
                    ->whereIn($this->associateKey, $idsToDelete);
                foreach ($attributes as $key => $value) {
                    $query->where($key, $value);
                }
                $query->delete();
            }

            // Insert new IDs
            if ($idsToInsert) {
                $data = array_map(function ($id) use ($attributes) {
                    return array_merge([
                        $this->foreignKey => $this->baseModel->getAttribute($this->baseModel->getPrimaryKey()),
                        $this->associateKey => $id,
                    ], $attributes);
                }, $idsToInsert);

                $query->insert($data);
            }
        });
    }

    /**
     * This method will add new records in the pivot table.
     * 
     * @param array $ids One or more ids to add.
     */
    /**
     * Add new records in the pivot table, supporting extra columns.
     * @param array|int $ids One or more ids to add.
     * @param array $attributes Extra columns to add to each row (optional).
     */
    public function attach($ids, array $attributes = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $query = new Query($this->pivotTable, $this->getConnection());

        $data = array_map(function ($id) use ($attributes) {
            return array_merge([
                $this->foreignKey => $this->baseModel->getAttribute($this->baseModel->getPrimaryKey()),
                $this->associateKey => $id,
            ], $attributes);
        }, $ids);

        if ($data) {
            $query->insertIgnore($data);
        }
    }

    /**
     * This method will remove records in the pivot table.
     * 
     * @param mixed $ids One or more ids to remove.
     */
    /**
     * Remove records in the pivot table, supporting extra columns as additional where filters.
     * @param array|int $ids One or more ids to remove.
     * @param array $attributes Extra columns to filter by (optional).
     */
    public function detach($ids, array $attributes = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $query = new Query($this->pivotTable, $this->getConnection());
        $query->where($this->foreignKey, '=', $this->baseModel->getAttribute($this->baseModel->getPrimaryKey()))
            ->whereIn($this->associateKey, $ids);
        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }
        $query->delete();
    }
}
