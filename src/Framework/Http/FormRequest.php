<?php

namespace Lightpack\Http;

use Lightpack\Container\Container;
use Lightpack\Exceptions\ValidationException;
use Lightpack\Session\Session;
use Lightpack\Validation\Validator;

abstract class FormRequest extends Request
{
    protected Validator $validator;

    abstract protected function rules();

    /**
     * @internal This method is for internal use only.
     */
    public function __boot(Validator $validator, Redirect $redirect, Session $session)
    {
        $container = Container::getInstance();
        $this->validator = $validator;

        $container->call($this, 'rules');
        $container->call($this, 'data');

        if ($validator->validateRequest()->passes()) {
            return;
        }

        if ($this->isAjax() || $this->expectsJson()) {
            $response = $container->get('response')->setStatus(422)->setMessage('Unprocessable Entity')->json([
                    'success' => false,
                    'message' => 'Request validation failed',
                    'errors' => $validator->getErrors(),
            ]);

            $container->call($this, 'beforeSend');

            $e = new ValidationException;
            $e->setResponse($response);
            throw $e;
        }

        $container->call($this, 'beforeRedirect');
        $redirect->back();

        throw new ValidationException;
    }

    /**
     * Manipulate request input data before validation
     */
    protected function data()
    {
        // ...
    }

    /**
     * Hook called before sending a JSON validation error response.
     * This is useful in API contexts where you want to modify the error response.
     */
    protected function beforeSend()
    {
        // ...
    }

    /**
     * Hook called before redirecting back on validation failure.
     * This is useful in web contexts where you want to modify the redirect response.
     */
    protected function beforeRedirect()
    {
        // ...
    }
}
