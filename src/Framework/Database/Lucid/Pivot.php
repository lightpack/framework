<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\Query\Query;

class Pivot extends Builder
{
    private $baseModel;
    private $pivotTable;
    private $foreignKey;
    private $associateKey;

    public function __construct(Model $model, Model $baseModel, $pivotTable, $foreignKey, $associateKey)
    {
        $this->baseModel = $baseModel;
        $this->pivotTable = $pivotTable;
        $this->foreignKey = $foreignKey;
        $this->associateKey = $associateKey;

        parent::__construct($model);
    }

    public function sync(array $ids): self
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

        return $this;
    }
}
