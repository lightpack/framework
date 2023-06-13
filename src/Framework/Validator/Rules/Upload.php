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

        // check if not required
        if (!in_array('required', $explodedRules) && request()->files()->isEmpty($field)) {
            return true;
        } elseif (request()->files()->isEmpty($field)) {
            $this->errorMessage = sprintf("You are required to upload %s.", str_replace(['_', '-'], ' ', $field));
            return false;
        }

        if ($upload instanceof UploadedFile) {
            return $this->processValidation($upload, $rules);
        }

        if (is_array($upload)) {
            // Check max_items rule
            $maxItemsRule = $this->getMaxItemsRule($explodedRules);

            if ($maxItemsRule && count($upload) > $maxItemsRule) {
                $this->errorMessage = sprintf("You can upload a maximum of %d files for %s.", $maxItemsRule, str_replace(['_', '-'], ' ', $field));
                return false;
            }

            // Check min_items rule
            $minItemsRule = $this->getMinItemsRule($explodedRules);

            if ($minItemsRule && count($upload) < $minItemsRule) {
                $this->errorMessage = sprintf("You need to upload at least %d files for %s.", $minItemsRule, str_replace(['_', '-'], ' ', $field));
                return false;
            }

            foreach ($upload as $file) {
                if (false == $this->processValidation($file, $rules)) {
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
}
