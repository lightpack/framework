<?php
namespace Lightpack\AI\Support;

class SchemaValidator
{
    protected array $errors = [];
    
    public function validate(array $params, array $schema): ?array
    {
        $this->errors = [];
        
        $normalized = $this->normalizeSchema($schema);
        
        foreach ($normalized as $key => $type) {
            if (!array_key_exists($key, $params) || $params[$key] === null) {
                $this->errors[] = "Missing required parameter: {$key}";
                continue;
            }
            
            $value = $params[$key];
            if (!$this->matchesType($value, $type)) {
                $this->errors[] = "Invalid parameter type for {$key}: expected {$type}";
                continue;
            }
            
            $params[$key] = $this->coerce($value, $type);
        }
        
        return empty($this->errors) ? $params : null;
    }
    
    public function errors(): array
    {
        return $this->errors;
    }
    
    protected function normalizeSchema(array $schema): array
    {
        $normalized = [];
        foreach ($schema as $key => $type) {
            if (is_int($key)) {
                $normalized[$type] = 'string';
            } else {
                $normalized[$key] = is_array($type) ? ($type[0] ?? 'string') : $type;
            }
        }
        return $normalized;
    }
    
    protected function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value) || is_numeric($value) || is_bool($value),
            'int' => is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1),
            'number' => is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)),
            'bool' => is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1',
            'array' => is_array($value),
            default => true,
        };
    }
    
    protected function coerce(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int' => (int)$value,
            'number' => (float)$value,
            'bool' => (bool)$value,
            'string' => (string)$value,
            default => $value,
        };
    }
}
