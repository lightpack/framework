<?php

namespace Lightpack\Http;

use Lightpack\Session\Session;
use Lightpack\Container\Container;
use Lightpack\Exceptions\ValidationException;
use Lightpack\Validation\Validator;

abstract class FormRequest extends Request
{
    protected Validator $validator;

    abstract protected function rules();

    /**
     * @internal This method is for internal use only.
     */
    public function __boot(Container $container, Validator $validator, Redirect $redirect, Session $session)
    {
        $this->validator = $validator;
        
        $container->call($this, 'rules');
        $container->call($this, 'data');

        $validator->setInput($this->input() + $_FILES);
        $validator->validate();

        if ($validator->passes()) {
            return;
        }

        if ($this->isAjax() || $this->isJson()) {
            $container->get('redirect')->setStatus(422)->setMessage('Unprocessable Entity')->json([
                    'success' => false,
                    'message' => 'Request validation failed',
                    'errors' => $validator->getErrors(),
            ]);

            $container->call($this, 'beforeSend');
            return;
        }

        $session->flash('_old_input', $this->input());
        $session->flash('_validation_errors', $validator->getErrors());
        $container->call($this, 'beforeRedirect');
        $redirect->back();

        throw new ValidationException();
    }


    protected function data()
    {
        // ...
    }

    protected function beforeSend()
    {
        // ...
    }

    protected function beforeRedirect()
    {
        // ...
    }
}