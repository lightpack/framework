<?php

namespace Lightpack\Http;

use Lightpack\Session\Session;
use Lightpack\Container\Container;
use Lightpack\Validation\Validator;

abstract class FormRequest extends Request
{
    public function __construct(
        protected Response $response, 
        protected Redirect $redirect, 
        protected Validator $validator,
        protected Container $container,
        protected Session $session,
    ) {
        parent::__construct();
    }

    abstract public function rules();

    public function __boot()
    {
        $this->container->call($this, 'rules');
        $this->container->call($this, 'data');
        $this->validator->setInput($this->input() + $_FILES);
        $this->validator->validate();

        if ($this->validator->passes()) {
            return;
        }

        if ($this->isAjax() || $this->isJson()) {
            $this->response->setStatus(422)->setMessage('Unprocessable Entity')->json([
                    'success' => false,
                    'message' => 'Request validation failed',
                    'errors' => $this->validator->getErrors(),
            ]);

            $this->container->call($this, 'beforeSend');
            $this->response->send();
        }

        $this->session->flash('_old_input', $this->input());
        $this->session->flash('_validation_errors', $this->validator->getErrors());
        $this->container->call($this, 'beforeRedirect');

        $this->redirect->back()->send();
    }


    protected function data(): void
    {
        // ...
    }

    protected function beforeSend(): void
    {
        // ...
    }

    protected function beforeRedirect(): void
    {
        // ...
    }
}