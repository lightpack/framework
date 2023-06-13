<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Http\UploadedFile;
use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Upload implements RuleInterface
{
    protected string $errorMessage;

    public function validate(array $dataSource, string $field, $rules = null)
    {
        $upload = (new Arr)->get($field, $dataSource);

        $explodedRules = explode('|', $rules);

        if (!$this->isRequired($explodedRules) && !$this->hasUploadedFile($field)) {
            return true;
        }

        if (!$this->hasUploadedFile($field)) {
            $this->errorMessage = sprintf("You are required to upload %s.", $this->getFieldDisplayName($field));
            return false;
        }

        if ($upload instanceof UploadedFile) {
            return $this->processValidation($upload, $rules);
        }

        if (is_array($upload)) {
            $maxItemsRule = $this->getMaxItemsRule($explodedRules);
            if ($maxItemsRule && count($upload) > $maxItemsRule) {
                $this->errorMessage = sprintf("You can upload a maximum of %d files for %s.", $maxItemsRule, $this->getFieldDisplayName($field));
                return false;
            }

            $minItemsRule = $this->getMinItemsRule($explodedRules);
            if ($minItemsRule && count($upload) < $minItemsRule) {
                $this->errorMessage = sprintf("You need to upload at least %d files for %s.", $minItemsRule, $this->getFieldDisplayName($field));
                return false;
            }

            foreach ($upload as $file) {
                if (!$this->processValidation($file, $rules)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public function getErrorMessage($field)
    {
        return $this->errorMessage;
    }

    private function processValidation(UploadedFile $file, string $rules): bool
    {
        $failed = $file->setRules($rules)->failedValidation();

        if ($failed) {
            $errors = $file->getValidationErrors();
            $this->errorMessage = implode(' ', $errors); // Store all errors
            return false;
        }

        return true;
    }

    private function getMaxItemsRule(array $rules): ?int
    {
        foreach ($rules as $rule) {
            if (strpos($rule, 'max_items:') === 0) {
                return (int) substr($rule, strlen('max_items:'));
            }
        }

        return null;
    }

    private function getMinItemsRule(array $rules): ?int
    {
        foreach ($rules as $rule) {
            if (strpos($rule, 'min_items:') === 0) {
                return (int) substr($rule, strlen('min_items:'));
            }
        }

        return null;
    }

    private function isRequired(array $rules): bool
    {
        return in_array('required', $rules);
    }

    private function hasUploadedFile(string $field): bool
    {
        return !request()->files()->isEmpty($field);
    }

    private function getFieldDisplayName(string $field): string
    {
        return str_replace(['_', '-'], ' ', $field);
    }
}
