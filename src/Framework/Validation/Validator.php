<?php

declare(strict_types=1);

namespace Lightpack\Validation;

use Lightpack\Container\Container;
use Lightpack\Utils\Arr;
use Lightpack\Validation\Rules\AfterRule;
use Lightpack\Validation\Rules\AlphaRule;
use Lightpack\Validation\Rules\AlphaNumRule;
use Lightpack\Validation\Rules\ArrayRule;
use Lightpack\Validation\Rules\BeforeRule;
use Lightpack\Validation\Rules\BetweenRule;
use Lightpack\Validation\Rules\BoolRule;
use Lightpack\Validation\Rules\CustomRule;
use Lightpack\Validation\Rules\DateRule;
use Lightpack\Validation\Rules\DifferentRule;
use Lightpack\Validation\Rules\EmailRule;
use Lightpack\Validation\Rules\File\FileExtensionRule;
use Lightpack\Validation\Rules\File\FileRule;
use Lightpack\Validation\Rules\File\FileSizeRule;
use Lightpack\Validation\Rules\File\FileTypeRule;
use Lightpack\Validation\Rules\File\ImageRule;
use Lightpack\Validation\Rules\File\MultipleFileRule;
use Lightpack\Validation\Rules\FloatRule;
use Lightpack\Validation\Rules\HasLowercaseRule;
use Lightpack\Validation\Rules\HasNumberRule;
use Lightpack\Validation\Rules\HasSymbolRule;
use Lightpack\Validation\Rules\HasUppercaseRule;
use Lightpack\Validation\Rules\InRule;
use Lightpack\Validation\Rules\IntRule;
use Lightpack\Validation\Rules\IpRule;
use Lightpack\Validation\Rules\LengthRule;
use Lightpack\Validation\Rules\MaxRule;
use Lightpack\Validation\Rules\MinRule;
use Lightpack\Validation\Rules\NotInRule;
use Lightpack\Validation\Rules\NumericRule;
use Lightpack\Validation\Rules\RegexRule;
use Lightpack\Validation\Rules\RequiredRule;
use Lightpack\Validation\Rules\SameRule;
use Lightpack\Validation\Rules\SlugRule;
use Lightpack\Validation\Rules\StringRule;
use Lightpack\Validation\Rules\UniqueRule;
use Lightpack\Validation\Rules\UrlRule;
use Lightpack\Validation\Traits\FileUploadValidationTrait;

class Validator
{
    use FileUploadValidationTrait;

    private array $rules = [];
    private array $data = [];
    private array $errors = [];
    private array $customRules = [];
    private string $currentField = '';
    private bool $valid = true;
    private Arr $arr;

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

    public function setInput(array $input): self
    {
        $this->data = $input;
        $this->errors = [];
        $this->valid = true;
        return $this;
    }

    public function passes(): bool
    {
        return $this->valid;
    }

    public function fails(): bool
    {
        return !$this->valid;
    }

    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function required(): self
    {
        $this->rules[$this->currentField][] = new RequiredRule;
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

    public function ip(?string $version = null): self
    {
        $this->rules[$this->currentField][] = new IpRule($version);
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

    public function array(?int $min = null, ?int $max = null): self
    {
        $this->rules[$this->currentField][] = new ArrayRule($min, $max);
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

    public function same(string $field): self
    {
        $rule = new SameRule($field, $this->arr);
        $this->rules[$this->currentField][] = $rule;
        return $this;
    }

    public function different(string $field): self
    {
        $rule = new DifferentRule($field, $this->arr);
        $this->rules[$this->currentField][] = $rule;
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
        $this->rules[$this->currentField][] = new CustomRule($callback, $message);
        return $this;
    }

    public function message(string $message): self
    {
        if (!empty($this->rules[$this->currentField])) {
            $lastRule = $this->rules[$this->currentField][count($this->rules[$this->currentField]) - 1];
            if (method_exists($lastRule, 'setMessage')) {
                $lastRule->setMessage($message);
            }
        }
        return $this;
    }

    public function addRule(string $name, callable $callback, string $message = 'Validation failed'): void
    {
        $this->customRules[$name] = new CustomRule($callback, $message);
    }

    public function __call(string $name, array $arguments): self
    {
        if (isset($this->customRules[$name])) {
            $this->rules[$this->currentField][] = $this->customRules[$name];
            return $this;
        }

        throw new \BadMethodCallException("Rule '{$name}' not found");
    }

    /**
     * Configure file validation rules
     */
    public function file(): self
    {
        $this->rules[$this->currentField][] = new FileRule();
        return $this;
    }

    public function fileSize(string $size): self
    {
        $this->rules[$this->currentField][] = new FileSizeRule($size);
        return $this;
    }

    public function fileType(array|string $types): self
    {
        $this->rules[$this->currentField][] = new FileTypeRule($types);
        return $this;
    }

    public function fileExtension(array|string $extensions): self
    {
        $this->rules[$this->currentField][] = new FileExtensionRule($extensions);
        return $this;
    }

    public function image(array $constraints = []): self
    {
        $this->rules[$this->currentField][] = new ImageRule($constraints);
        return $this;
    }

    public function multipleFiles(?int $min = null, ?int $max = null): self
    {
        $this->rules[$this->currentField][] = new MultipleFileRule($min, $max);
        return $this;
    }

    public function hasUppercase(): self 
    {
        $this->rules[$this->currentField][] = new HasUppercaseRule();
        return $this;
    }

    public function hasLowercase(): self 
    {
        $this->rules[$this->currentField][] = new HasLowercaseRule();
        return $this;
    }

    public function hasNumber(): self 
    {
        $this->rules[$this->currentField][] = new HasNumberRule();
        return $this;
    }

    public function hasSymbol(): self 
    {
        $this->rules[$this->currentField][] = new HasSymbolRule();
        return $this;
    }

    public function validate(): self
    {
        foreach ($this->rules as $field => $rules) {
            $value = $this->arr->get($field, $this->data);
            
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

    public function validateRequest(): self
    {
        $container = Container::getInstance();
        $request = $container->get('request');

        $input = $request->input() + $_FILES;

        $this->setInput($input);
        $this->validate();

        if($request->isAjax() || $request->expectsJson()) {
            return $this;
        }

        if($this->fails()) {
            $session = $container->get('session');

            $session->flash('_old_input', $request->input());
            $session->flash('_validation_errors', $this->getErrors());
        }

        return $this;
    }

    private function validateField(string $field, mixed $value, array $rules): void
    {
        $isOptional = true;

        foreach ($rules as $index => $rule) {
            if ($rule instanceof RequiredRule) {
                $isOptional = false;
            }

            // For optional file uploads, skip validation if no file was uploaded
            if ($index === 0 && $isOptional && is_array($value)) {
                if ($this->isEmptySingleFileUpload($value) || $this->isEmptyMultiFileUpload($value)) {
                    return;
                }
            }

            if ($isOptional && empty($value)) {
                return;
            }

            if (!$rule($value, $this->data)) {
                $this->errors[$field] = $rule->getMessage();
                $this->valid = false;
                break;
            }
        }
    }

    private function validateWildcard(string $field, mixed $value, array $rules): void
    {
        if (!is_array($value)) {
            $this->errors[$field] = 'Field must be an array';
            $this->valid = false;
            return;
        }

        // For regular arrays, validate each item
        foreach ($value as $key => $item) {
            $actualField = str_replace('*', (string) $key, $field);
            $this->validateField($actualField, $item, $rules);
        }
    }
}
