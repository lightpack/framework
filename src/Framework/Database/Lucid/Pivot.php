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
     * @param Model $baseModel The relating model class name.
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
    public function sync(array $ids)
    {
        // Get query builder for pivot table
        $query = new Query($this->pivotTable, $this->getConnection());

        // Delete all pivot rows
        $query->where($this->foreignKey, '=', $this->baseModel->id)->delete();

        // Prepare data for pivot table
        $data = array_map(function ($id) {
            return [
                $this->foreignKey => $this->baseModel->id,
                $this->associateKey => $id,
            ];
        }, $ids);

        // Insert new pivot rows
        if ($data) {
            $query->bulkInsert($data);
        }
    }

    /**
     * This method will add new records in the pivot table.
     */
    public function attach(...$ids)
    {
        // Get query builder for pivot table
        $query = new Query($this->pivotTable, $this->getConnection());

        // Insert new pivot rows ignoring existing ones
        foreach ($ids as $id) {
            $query->insertIgnore([
                $this->foreignKey => $this->baseModel->id,
                $this->associateKey => $id,
            ]);
        }
    }

    /**
     * This method will remove records in the pivot table.
     */
    public function detach(...$ids)
    {
        // Get query builder for pivot table
        $query = new Query($this->pivotTable, $this->getConnection());

        // Delete pivot rows
        $query->where($this->foreignKey, '=', $this->baseModel->id)->whereIn($this->associateKey, $ids)->delete();
    }
}
