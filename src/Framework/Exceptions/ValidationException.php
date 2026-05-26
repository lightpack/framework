<?php

namespace Lightpack\Exceptions;

use Lightpack\Http\Response;

class ValidationException extends HttpException
{
    protected ?Response $response = null;

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
