<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Http\UploadedFile;
use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Upload implements RuleInterface
{   
    public function validate(array $dataSource, string $field, $rules = null)
    {
        $upload = (new Arr)->get($field, $dataSource);

        $xplodedRules = explode('|', $rules);

        // check if not required
        if(!in_array('required', $xplodedRules) && request()->files()->isEmpty($field)) {
            return true;
        }

        if($upload instanceof UploadedFile) {
            return $upload->setRules($rules)->passedValidation();
        }

        if(is_array($upload)) {
            foreach($upload as $file) {
                if(false == $file->setRules($rules)->passedValidation()){
                    return false;
                }
            }

            return true;
        }

        return false;
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("The %s appears to be an invalid upload.", $field);
    }
}