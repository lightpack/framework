<?php

declare(strict_types=1);

namespace Lightpack\Utils;

class Validator
{
    private array $rules = [];
    private array $data = [];
    private array $errors = [];
    private array $customRules = [];
    private string $currentField = '';
    private bool $valid = true;
    private ?Arr $arr = null;

    public function __construct()
    {
        $this->arr = new Arr;
    }

    public function field(string $field): self 
    {
        $this->currentField = $field;
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        return $this;
    }

    public function validate(array &$data): self
    {
        $this->data = &$data;
        $this->errors = [];
        $this->valid = true;
        
        foreach ($this->rules as $field => $rules) {
            $value = $this->arr->get($field, $data);
            
            if (str_contains($field, '*')) {
                $this->validateWildcard($field, $value, $rules);
                continue;
            }

            $this->validateField($field, $value, $rules);
        }
        
        // Reset rules after validation
        $this->rules = [];
        $this->currentField = '';
        
        return $this;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFieldErrors(string $field): array
    {
        return [$this->errors[$field]] ?? [];
    }

    public function getData(): array
    {
        return $this->data;
    }

    // Validation Rules

    public function required(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'required',
            'params' => [],
            'message' => 'Field is required',
            'callback' => fn($value) => $value !== null && $value !== '' || is_bool($value),
        ];
        return $this;
    }

    public function requiredIf(string $field, mixed $value = null): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'requiredIf',
            'params' => [$field, $value],
            'message' => 'Field is required',
            'callback' => function($fieldValue) use ($field, $value) {
                $dependentValue = $this->arr->get($field, $this->data);
                
                // If no specific value is provided, check if dependent field is truthy
                if ($value === null) {
                    $required = $dependentValue === true || $dependentValue === 1 || $dependentValue === '1';
                } else {
                    $required = $dependentValue === $value;
                }

                // If not required, any value is valid
                if (!$required) {
                    return true;
                }

                // If required, must have a non-null value
                return $fieldValue !== null;
            },
        ];
        return $this;
    }

    public function email(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'email',
            'params' => [],
            'message' => 'Invalid email format',
            'callback' => fn($value) => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
        ];
        return $this;
    }

    public function min(int $length): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'min',
            'params' => [$length],
            'message' => "Minimum length is {$length}",
            'callback' => fn($value) => mb_strlen((string) $value) >= $length,
        ];
        return $this;
    }

    public function max(int $length): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'max',
            'params' => [$length],
            'message' => "Maximum length is {$length}",
            'callback' => fn($value) => mb_strlen((string) $value) <= $length,
        ];
        return $this;
    }

    public function length(int $length): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'length',
            'params' => [$length],
            'message' => "Length must be exactly {$length} characters",
            'callback' => function($value) use ($length) {
                if ($value === null) {
                    return false;
                }
                return mb_strlen((string) $value) === $length;
            },
        ];
        return $this;
    }

    public function numeric(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'numeric',
            'params' => [],
            'message' => 'Must be numeric',
            'callback' => fn($value) => is_numeric($value),
        ];
        return $this;
    }

    public function string(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'string',
            'params' => [],
            'message' => 'Must be a string',
            'callback' => fn($value) => is_string($value) || (is_numeric($value) && !is_bool($value)),
        ];
        return $this;
    }

    public function alpha(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'alpha',
            'params' => [],
            'message' => 'Must contain only letters',
            'callback' => fn($value) => is_string($value) && preg_match('/^[\p{L}\p{M}]+$/u', $value),
        ];
        return $this;
    }

    public function alphaNum(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'alphaNum',
            'params' => [],
            'message' => 'Must contain only letters and numbers',
            'callback' => fn($value) => is_string($value) && preg_match('/^[\p{L}\p{M}\p{N}]+$/u', $value),
        ];
        return $this;
    }

    public function slug(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'slug',
            'params' => [],
            'message' => 'Must be a valid URL slug (lowercase letters, numbers, and hyphens only)',
            'callback' => fn($value) => is_string($value) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value),
        ];
        return $this;
    }

    public function int(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'int',
            'params' => [],
            'message' => 'Must be an integer',
            'callback' => fn($value) => is_int($value) || (is_string($value) && ctype_digit($value)),
        ];
        return $this;
    }

    public function float(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'float',
            'params' => [],
            'message' => 'Must be a float',
            'callback' => fn($value) => is_float($value) || (is_string($value) && is_numeric($value) && str_contains($value, '.')),
        ];
        return $this;
    }

    public function bool(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'bool',
            'params' => [],
            'message' => 'Must be a boolean',
            'callback' => fn($value) => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false', true, false], false),
        ];
        return $this;
    }

    public function array(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'array',
            'params' => [],
            'message' => 'Must be an array',
            'callback' => fn($value) => is_array($value),
        ];
        return $this;
    }

    public function date(?string $format = null): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'date',
            'params' => [$format],
            'message' => $format ? "Must be a valid date in format: {$format}" : 'Must be a valid date',
            'callback' => function($value) use ($format) {
                if ($format) {
                    $date = \DateTime::createFromFormat($format, $value);
                    return $date && $date->format($format) === $value;
                }
                return strtotime($value) !== false;
            },
        ];
        return $this;
    }

    public function before(string $date, ?string $format = null): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'before',
            'params' => [$date, $format],
            'message' => $format 
                ? "Date must be before {$date} (format: {$format})" 
                : "Date must be before {$date}",
            'callback' => function($value) use ($date, $format) {
                if ($format) {
                    $valueDate = \DateTime::createFromFormat($format, $value);
                    $compareDate = \DateTime::createFromFormat($format, $date);
                    return $valueDate && $compareDate && $valueDate->format($format) === $value && $valueDate < $compareDate;
                }
                $valueTime = strtotime($value);
                $compareTime = strtotime($date);
                return $valueTime !== false && $compareTime !== false && $valueTime < $compareTime;
            },
        ];
        return $this;
    }

    public function after(string $date, ?string $format = null): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'after',
            'params' => [$date, $format],
            'message' => $format 
                ? "Date must be after {$date} (format: {$format})" 
                : "Date must be after {$date}",
            'callback' => function($value) use ($date, $format) {
                if ($format) {
                    $valueDate = \DateTime::createFromFormat($format, $value);
                    $compareDate = \DateTime::createFromFormat($format, $date);
                    return $valueDate && $compareDate && $valueDate->format($format) === $value && $valueDate > $compareDate;
                }
                $valueTime = strtotime($value);
                $compareTime = strtotime($date);
                return $valueTime !== false && $compareTime !== false && $valueTime > $compareTime;
            },
        ];
        return $this;
    }

    public function url(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'url',
            'params' => [],
            'message' => 'Must be a valid URL',
            'callback' => fn($value) => filter_var($value, FILTER_VALIDATE_URL) !== false,
        ];
        return $this;
    }

    public function ip(?string $version = null): self
    {
        $message = match($version) {
            'v4' => 'Must be a valid IPv4 address',
            'v6' => 'Must be a valid IPv6 address',
            null => 'Must be a valid IP address',
            default => throw new \InvalidArgumentException("Invalid IP version: {$version}. Use 'v4', 'v6' or null."),
        };

        $callback = match($version) {
            'v4' => fn($value) => filter_var($value, FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_IPV4]) !== false,
            'v6' => fn($value) => filter_var($value, FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_IPV6]) !== false,
            null => fn($value) => filter_var($value, FILTER_VALIDATE_IP) !== false,
            default => fn($value) => false,
        };

        $this->rules[$this->currentField][] = [
            'rule' => 'ip',
            'params' => [$version],
            'message' => $message,
            'callback' => $callback,
        ];
        return $this;
    }

    public function between(int|float $min, int|float $max): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'between',
            'params' => [$min, $max],
            'message' => "Must be between {$min} and {$max}",
            'callback' => function($value) use ($min, $max) {
                if (!is_numeric($value)) {
                    return false;
                }
                $numericValue = (float) $value;
                return $numericValue >= $min && $numericValue <= $max;
            },
        ];
        return $this;
    }

    public function unique(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'unique',
            'params' => [],
            'message' => 'Values must be unique',
            'callback' => function($value) {
                if (!is_array($value)) {
                    return true;
                }
                return count($value) === count(array_unique($value));
            },
        ];
        return $this;
    }

    public function nullable(): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'nullable',
            'params' => [],
            'message' => '',
            'callback' => fn($value) => true,
            'nullable' => true,
        ];
        return $this;
    }

    public function same(string $field): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'same',
            'params' => [$field],
            'message' => "Must match {$field}",
            'callback' => fn($value) => $value === $this->arr->get($field, $this->data),
        ];
        return $this;
    }

    public function different(string $field): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'different',
            'params' => [$field],
            'message' => "Must be different from {$field}",
            'callback' => fn($value) => $value !== $this->arr->get($field, $this->data),
        ];
        return $this;
    }

    public function in(array $values): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'in',
            'params' => [$values],
            'message' => "Must be one of: " . implode(', ', $values),
            'callback' => fn($value) => in_array($value, $values, true),
        ];
        return $this;
    }

    public function notIn(array $values): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'notIn',
            'params' => [$values],
            'message' => "Must not be one of: " . implode(', ', $values),
            'callback' => fn($value) => !in_array($value, $values, true),
        ];
        return $this;
    }

    public function regex(string $pattern): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'regex',
            'params' => [$pattern],
            'message' => "Must match pattern: {$pattern}",
            'callback' => fn($value) => preg_match($pattern, $value) === 1,
        ];
        return $this;
    }

    public function custom(callable $callback, string $message = 'Validation failed'): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'custom',
            'params' => [],
            'message' => $message,
            'callback' => $callback,
        ];
        return $this;
    }

    public function transform(callable $callback): self
    {
        $this->rules[$this->currentField][] = [
            'rule' => 'transform',
            'params' => [],
            'message' => '',
            'callback' => $callback,
            'transform' => true,
        ];
        return $this;
    }

    public function message(string $message): self
    {
        if (!empty($this->rules[$this->currentField])) {
            $lastRule = &$this->rules[$this->currentField][count($this->rules[$this->currentField]) - 1];
            $lastRule['message'] = $message;
        }
        return $this;
    }

    public function addRule(string $name, callable $callback, string $message = 'Validation failed'): void
    {
        $this->customRules[$name] = [
            'callback' => $callback,
            'message' => $message,
        ];
    }

    public function __call(string $name, array $arguments): self
    {
        if (isset($this->customRules[$name])) {
            $rule = $this->customRules[$name];
            $this->rules[$this->currentField][] = [
                'rule' => $name,
                'params' => $arguments,
                'message' => $rule['message'],
                'callback' => $rule['callback'],
            ];
            return $this;
        }

        throw new \BadMethodCallException("Rule '{$name}' not found");
    }

    private function validateField(string $field, $value, array $rules): void
    {
        $fieldErrors = [];

        foreach ($rules as $rule) {
            // For boolean fields, false is a valid value
            if ($rule['rule'] === 'bool' && is_bool($value)) {
                $valid = $rule['callback']($value, ...$rule['params']);
                if ($valid === false) {
                    $fieldErrors[$rule['rule']] = $rule['message'];
                }
                continue;
            }

            // Only consider truly empty values (null or empty string)
            $isEmpty = $value === null || $value === '';
            
            if ($isEmpty) {
                if (isset($rule['nullable']) && $rule['nullable']) {
                    continue;
                }
                // Don't skip length rule for empty values
                if (!in_array($rule['rule'], ['required', 'requiredIf', 'length'])) {
                    continue;
                }
            }

            if (isset($rule['transform']) && $rule['transform']) {
                $value = $rule['callback']($value, ...$rule['params']);
                $parts = explode('.', $field);
                $key = array_pop($parts);
                $target = &$this->data;
                
                foreach ($parts as $part) {
                    if (!isset($target[$part]) || !is_array($target[$part])) {
                        $target[$part] = [];
                    }
                    $target = &$target[$part];
                }
                
                $target[$key] = $value;
                continue;
            }

            $valid = $rule['callback']($value, ...$rule['params']);
            
            if ($valid === false) {
                $fieldErrors[$rule['rule']] = $rule['message'];
            }
        }

        if (!empty($fieldErrors)) {
            $this->valid = false;
            $this->errors[$field] = reset($fieldErrors);
        }
    }

    private function validateWildcard(string $field, $values, array $rules): void
    {
        if (!is_array($values)) {
            return;
        }

        foreach ($values as $index => $value) {
            $indexedField = str_replace('*', (string) $index, $field);
            $this->validateField($indexedField, $value, $rules);
        }
    }
}
