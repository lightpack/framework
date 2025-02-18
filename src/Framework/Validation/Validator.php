<?php

declare(strict_types=1);

namespace Lightpack\Validation;

use Lightpack\Utils\Arr;
use Lightpack\Validation\Rules\AfterRule;
use Lightpack\Validation\Rules\AlphaRule;
use Lightpack\Validation\Rules\AlphaNumRule;
use Lightpack\Validation\Rules\ArrayRule;
use Lightpack\Validation\Rules\BeforeRule;
use Lightpack\Validation\Rules\BetweenRule;
use Lightpack\Validation\Rules\BoolRule;
use Lightpack\Validation\Rules\DateRule;
use Lightpack\Validation\Rules\DifferentRule;
use Lightpack\Validation\Rules\EmailRule;
use Lightpack\Validation\Rules\FloatRule;
use Lightpack\Validation\Rules\InRule;
use Lightpack\Validation\Rules\IntRule;
use Lightpack\Validation\Rules\IpRule;
use Lightpack\Validation\Rules\LengthRule;
use Lightpack\Validation\Rules\MaxRule;
use Lightpack\Validation\Rules\MinRule;
use Lightpack\Validation\Rules\NotInRule;
use Lightpack\Validation\Rules\NullableRule;
use Lightpack\Validation\Rules\NumericRule;
use Lightpack\Validation\Rules\RegexRule;
use Lightpack\Validation\Rules\RequiredRule;
use Lightpack\Validation\Rules\RequiredIfRule;
use Lightpack\Validation\Rules\SameRule;
use Lightpack\Validation\Rules\SlugRule;
use Lightpack\Validation\Rules\StringRule;
use Lightpack\Validation\Rules\UniqueRule;
use Lightpack\Validation\Rules\UrlRule;

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

    public function required(): self
    {
        $this->rules[$this->currentField][] = new RequiredRule;
        return $this;
    }

    public function requiredIf(string $field, mixed $value = null): self
    {
        $this->rules[$this->currentField][] = new RequiredIfRule($field, $value, $this->data);
        return $this;
    }

    public function email(): self
    {
        $this->rules[$this->currentField][] = new EmailRule;
        return $this;
    }

    public function min(int $length): self
    {
        $this->rules[$this->currentField][] = new MinRule($length);
        return $this;
    }

    public function max(int $length): self
    {
        $this->rules[$this->currentField][] = new MaxRule($length);
        return $this;
    }

    public function length(int $length): self
    {
        $this->rules[$this->currentField][] = new LengthRule($length);
        return $this;
    }

    public function numeric(): self
    {
        $this->rules[$this->currentField][] = new NumericRule;
        return $this;
    }

    public function string(): self
    {
        $this->rules[$this->currentField][] = new StringRule;
        return $this;
    }

    public function alpha(): self
    {
        $this->rules[$this->currentField][] = new AlphaRule;
        return $this;
    }

    public function alphaNum(): self
    {
        $this->rules[$this->currentField][] = new AlphaNumRule;
        return $this;
    }

    public function slug(): self
    {
        $this->rules[$this->currentField][] = new SlugRule;
        return $this;
    }

    public function date(): self
    {
        $this->rules[$this->currentField][] = new DateRule;
        return $this;
    }

    public function url(): self
    {
        $this->rules[$this->currentField][] = new UrlRule;
        return $this;
    }

    public function ip(): self
    {
        $this->rules[$this->currentField][] = new IpRule;
        return $this;
    }

    public function int(): self
    {
        $this->rules[$this->currentField][] = new IntRule;
        return $this;
    }

    public function float(): self
    {
        $this->rules[$this->currentField][] = new FloatRule;
        return $this;
    }

    public function bool(): self
    {
        $this->rules[$this->currentField][] = new BoolRule;
        return $this;
    }

    public function array(): self
    {
        $this->rules[$this->currentField][] = new ArrayRule;
        return $this;
    }

    public function before(string $date, ?string $format = null): self
    {
        $this->rules[$this->currentField][] = new BeforeRule($date, $format);
        return $this;
    }

    public function after(string $date, ?string $format = null): self
    {
        $this->rules[$this->currentField][] = new AfterRule($date, $format);
        return $this;
    }

    public function between(int|float $min, int|float $max): self
    {
        $this->rules[$this->currentField][] = new BetweenRule($min, $max);
        return $this;
    }

    public function unique(): self
    {
        $this->rules[$this->currentField][] = new UniqueRule;
        return $this;
    }

    public function nullable(): self
    {
        $this->rules[$this->currentField][] = new NullableRule;
        return $this;
    }

    public function same(string $field): self
    {
        $this->rules[$this->currentField][] = new SameRule($field, $this->data);
        return $this;
    }

    public function different(string $field): self
    {
        $this->rules[$this->currentField][] = new DifferentRule($field, $this->data);
        return $this;
    }

    public function in(array $values): self
    {
        $this->rules[$this->currentField][] = new InRule($values);
        return $this;
    }

    public function notIn(array $values): self
    {
        $this->rules[$this->currentField][] = new NotInRule($values);
        return $this;
    }

    public function regex(string $pattern): self
    {
        $this->rules[$this->currentField][] = new RegexRule($pattern);
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

    private function validateField(string $field, mixed $value, array $rules): void
    {
        foreach ($rules as $rule) {
            if (!$rule($value)) {
                $this->errors[$field] = $rule->getMessage();
                $this->valid = false;
                break;
            }
        }
    }

    private function validateWildcard(string $field, mixed $value, array $rules): void
    {
        if (!is_array($value)) {
            return;
        }

        foreach ($value as $key => $item) {
            $actualField = str_replace('*', $key, $field);
            foreach ($rules as $rule) {
                if (!$rule($item)) {
                    $this->errors[$actualField] = $rule->getMessage();
                    $this->valid = false;
                    break;
                }
            }
        }
    }
}
