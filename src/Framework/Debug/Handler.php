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
use Lightpack\Exceptions\ValidationException;

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

        if ($exc instanceof ValidationException) {
            return $this->handleFormRequestValidationException($exc);
        }

        if ($exc instanceof Exception) {
            return $this->logAndRenderException($exc, 'Exception');
        }

        $this->handleError(E_ERROR, "Fatal error: {$exc->getMessage()}", $exc->getFile(), $exc->getLine());
    }

    private function logAndRenderException(Throwable $exc, $type = 'Error')
    {
        $this->logger->error($exc);
        $this->exceptionRenderer->render($exc, $type);
    }

    private function handleFormRequestValidationException(ValidationException $exc)
    {
        /** @var \Lightpack\Container\Container $container */
        $container = Container::getInstance();

        // For ajax or json requests, return json response.
        if ($container->get('request')->isAjax() || $container->get('request')->isJson()) {
            $container->get('response')
                ->setStatus(422)
                ->setMessage('Unprocessable Entity')
                ->json([
                    'success' => false,
                    'message' => $exc->getMessage(),
                    'errors' => $exc->getErrors(),
                ])->send();
        }

        // Redirect to previous page with errors and old input.
        $container->get('session')->flash('_old_input', $container->get('request')->input());
        $container->get('session')->flash('_validation_errors', $exc->getErrors());
        $container->get('redirect')->back()->send();
    }
}
