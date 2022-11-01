<?php

namespace Lightpack\Debug;

use Throwable;
use TypeError;
use ParseError;
use ErrorException;
use Exception;
use Lightpack\Container\Container;
use Psr\Log\LoggerInterface;
use Lightpack\Debug\ExceptionRenderer;
use Lightpack\Exceptions\FormRequestValidationException;

class Handler
{
    private $logger;
    private $exceptionRenderer;

    public function __construct(
        LoggerInterface $logger,
        ExceptionRenderer $exceptionRenderer
    ) {
        $this->logger = $logger;
        $this->exceptionRenderer = $exceptionRenderer;
    }

    public function handleError(int $code, string $message, string $file, int $line)
    {
        $exc = new ErrorException(
            $message,
            $code,
            $code,
            $file,
            $line
        );

        $this->logAndRenderException($exc);
    }

    public function handleShutdown()
    {
        $error = error_get_last();

        if ($error) {
            $this->handleError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }

    public function handleException(Throwable $exc)
    {
        if ($exc instanceof ParseError) {
            return $this->handleError(E_PARSE, "Parse error: {$exc->getMessage()}", $exc->getFile(), $exc->getLine());
        } 
        
        if ($exc instanceof TypeError) {
            return $this->handleError(E_RECOVERABLE_ERROR, "Type error: {$exc->getMessage()}", $exc->getFile(), $exc->getLine());
        }

        if($exc instanceof FormRequestValidationException) {
            return $this->handleFormRequestValidationException($exc);
        }

        if($exc instanceof Exception)
        {
            return $this->logAndRenderException($exc, 'Exception');
        }

        $this->handleError(E_ERROR, "Fatal error: {$exc->getMessage()}", $exc->getFile(), $exc->getLine());
    }

    private function logAndRenderException(Throwable $exc, $type = 'Error')
    {
        $this->logger->error($exc);
        $this->exceptionRenderer->render($exc, $type);
    }

    private function handleFormRequestValidationException()
    {
        Container::getInstance()->get('redirect')->back()->send();
    }
}
