<?php

namespace Lightpack\Factory;

abstract class ModelFactory extends Factory
{
    /**
     * @inheritDoc
     */
    abstract protected function template(): array;

    /**
     * Concrete factories must return the model class name.
     * @return string
     */
    abstract protected function model(): string;

    /**
     * Create and save model instance(s) from factory data.
     * Returns a single Model or array of Models depending on batch mode.
     */
    public function save(array $overrides = [])
    {
        $data = $this->make($overrides);
        $modelClass = $this->model();

        if ($this->isBatch($data)) {
            return $this->saveBatch($modelClass, $data);
        }

        return $this->saveSingle($modelClass, $data);
    }

    /**
     * Determine if the data represents a batch.
     */
    protected function isBatch($data): bool
    {
        return is_array($data) && isset($data[0]) && is_array($data[0]);
    }

    /**
     * Save multiple models in batch mode.
     * @param string $modelClass
     * @param array $batchData
     * @return array
     */
    protected function saveBatch(string $modelClass, array $batchData): array
    {
        $models = [];
        foreach ($batchData as $attributes) {
            $models[] = $this->saveSingle($modelClass, $attributes);
        }
        return $models;
    }

    /**
     * Save a single model instance.
     * @param string $modelClass
     * @param array $attributes
     * @return object
     */
    protected function saveSingle(string $modelClass, array $attributes)
    {
        $model = new $modelClass();
        foreach ($attributes as $key => $value) {
            $model->{$key} = $value;
        }
        $model->insert();
        return $model;
    }
}
