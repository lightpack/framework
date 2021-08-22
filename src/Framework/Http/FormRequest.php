<?php

namespace Lightpack\Http;

use Lightpack\Validator\Validator;

class FormRequest
{
    protected $formData = [];
    protected $formErrors = [];
    protected $validator;

    public function __construct(array $rules)
    {
        $this->setData(array_keys($rules));
        $this->setValidator();
        $this->setValidationRules($rules);
        $this->runValidation();
        $this->setFlashMessages();
    }
    
    public function hasErrors()
    {
        return $this->validator->hasErrors();
    }
    
    public function getErrors()
    {
        return $this->validator->getErrors();
    }

    public function getData()
    {
        return app('session')->flash('form_data');
    }

    protected function setData(array $fields)
    {
        $data = $this->prepareFormRequestData();

        foreach($fields as $field) {
            $this->formData[$field] = $data[$field] ?? null;
        }
    }

    protected function setValidator()
    {
        $this->validator = new Validator($this->formData);
    }

    protected function setValidationRules(array $rules)
    {
        foreach($rules as $field => $rule) {
            $rule = is_string($rule) ? trim($rule) : $rule;
                
            if($rule) {
                $this->validator->setRule($field, $rule);
            }
        }
    }

    protected function runValidation()
    {
        $this->validator->run();
    }

    protected function setFlashMessages()
    {
        app('session')->flash('form_data', $this->formData);

        if(!$this->validator->hasErrors()) {
            return;
        }

        app('session')->flash('form_errors', $this->validator->getErrors());
    }

    private function prepareFormRequestData(): array
    {
        if(app('request')->isGet()) {
            return app('request')->get();
        }

        if(app('request')->isJson()) {
            return app('request')->body();
        }

        return array_merge(
            app('request')->post(),
            app('request')->files()->get() ?? []
        );
    }
}