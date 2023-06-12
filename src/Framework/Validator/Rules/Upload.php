<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Http\UploadedFile;
use Lightpack\Utils\Arr;
use Lightpack\Utils\Str;
use Lightpack\Validator\RuleInterface;

class Upload implements RuleInterface
{   
    protected string $errorMessage;

    public function validate(array $dataSource, string $field, $rules = null)
    {
        $upload = (new Arr)->get($field, $dataSource);

        $xplodedRules = explode('|', $rules);

        // check if not required
        if(!in_array('required', $xplodedRules) && request()->files()->isEmpty($field)) {
            return true;
        } elseif(request()->files()->isEmpty($field)) {
            $this->errorMessage = sprintf("You are required to upload %s.", str_replace(['_', '-'], ' ', $field));
            return false;
        }

        if($upload instanceof UploadedFile) {
            return $this->processValidation($upload, $rules);
        }

        if(is_array($upload)) {
            foreach($upload as $file) {
                if(false == $this->processValidation($file, $rules)){
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

        if($failed) {
            $errors = $file->getValidationErrors();

            $this->errorMessage = reset($errors);

            return false;
        }

        return true;
    }
}