<?php

namespace Lightpack\Database\Lucid;

use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Lucid\Collection;

abstract class Transformer
{
    protected array $includes = [];
    protected array $fields = [];
    protected ?string $currentRelation = null;

    abstract protected function data(Model $model): array;

    public function transform(Model|Collection $model): array
    {
        if (is_null($model)) {
            return [];
        }

        if ($model instanceof Collection) {
            $result = [];
            foreach ($model as $item) {
                $result[] = $this->transform($item);
            }
            return $result;
        }

        $data = $this->data($model);

        if ($this->fields) {
            $data = $this->filterFields($data);
        }

        return $this->loadIncludes($model, $data);
    }

    public function includes($relations): self
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }

        foreach ($relations as $include) {
            [$relation, $nested] = $this->parseInclude($include);
            $this->includes[$relation] = $nested;
        }

        return $this;
    }

    public function fields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    protected function loadIncludes($model, array $data): array
    {
        foreach ($this->includes as $relation => $nested) {

            if ($this->shouldLoadRelation($model, $relation)) {
                $data[$relation] = $this->transformRelation(
                    $relation,
                    $model->{$relation},
                    $this->resolveTransformer($relation),
                    $nested
                );
            }
        }

        return $data;
    }

    protected function transformRelation(string $relationName, $relation, $transformer, array $nested = []): array
    {
        if (is_null($relation)) {
            return [];
        }

        $this->currentRelation = $relationName;
        $transformer = $transformer->includes($nested)->fields($this->getNestedFields());

        if ($relation instanceof Collection) {
            $results = [];
            foreach ($relation as $item) {
                $results[] = $transformer->transform($item);
            }
            return $results;
        }

        return $transformer->transform($relation);
    }

    protected function parseInclude(string $include): array
    {
        $parts = explode('.', $include);
        return [
            array_shift($parts),
            $parts
        ];
    }

    /**
     * Define transformer mappings for relations
     */
    protected function transformerMap(): array
    {
        return [];
    }

    protected function resolveTransformer(string $relation): Transformer
    {
        $transformers = $this->transformerMap();
        
        if (!isset($transformers[$relation])) {
            throw new \RuntimeException(
                sprintf(
                    "No transformer defined for relation '%s'. Define it in %s::transformerMap().", 
                    $relation,
                    get_class($this)
                )
            );
        }

        $transformer = $transformers[$relation];
        if (is_string($transformer)) {
            $transformer = new $transformer;
        }

        if (!$transformer instanceof Transformer) {
            throw new \RuntimeException(
                sprintf(
                    "Invalid transformer for relation '%s'. Must be a Transformer instance or class name.",
                    $relation
                )
            );
        }

        return $transformer;
    }

    protected function getNestedFields(): array
    {
        if (!$this->currentRelation) {
            return [];
        }

        $nestedFields = [];

        // Add direct fields for this relation as 'self'
        if (isset($this->fields[$this->currentRelation])) {
            $nestedFields['self'] = $this->fields[$this->currentRelation];
        }

        // Add nested fields
        foreach ($this->fields as $key => $fields) {
            if (str_starts_with($key, $this->currentRelation . '.')) {
                $nestedKey = str_replace($this->currentRelation . '.', '', $key);
                $nestedFields[$nestedKey] = $fields;
            }
        }

        return $nestedFields;
    }

    protected function filterFields(array $data): array
    {
        // Only filter if fields are explicitly specified
        if (!isset($this->fields['self'])) {
            return $data;
        }

        $filtered = [];
        foreach ($this->fields['self'] as $field) {
            if (array_key_exists($field, $data)) {
                $filtered[$field] = $data[$field];
            }
        }
        return $filtered;
    }

    protected function shouldLoadRelation(Model $model, string $relation): bool
    {
        if (!method_exists($model, $relation)) {
            return false;
        }

        // Don't load if already eager loaded
        if (array_key_exists($relation, $model->getLoadedRelations())) {
            return false;
        }

        return true;
    }
}
