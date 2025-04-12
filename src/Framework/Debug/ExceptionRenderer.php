<?php

namespace Lightpack\Debug;

use Throwable;
use Lightpack\Exceptions\HttpException;

class ExceptionRenderer
{
    private $environment;
    private $errorLevels = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
    ];

    public function __construct(string $environment)
    {
        $this->environment = $environment;
    }

    public function render(Throwable $exc, string $errorType = 'Exception'): void
    {
        // Clean the output buffer first.
        while(\ob_get_level() !== 0) {
            \ob_end_clean();
        }

        if(PHP_SAPI === 'cli') {
            $this->renderCli($exc, $errorType);
        }elseif($this->environment !== 'development') {
            $this->renderProductionTemplate($exc);
        } else {
            $this->renderDevelopmentTemplate($exc, $errorType);
        }
    }

    private function getErrorType(int $code): string
    {
        return $this->errorLevels[$code] ?? 'Error';
    }

    private function getTrace(string $traceString): string
    {
        $traceFragments = preg_split('/#[\d+]/', $traceString);

        unset($traceFragments[0]);
        array_pop($traceFragments);

        $trace = '';

        foreach ($traceFragments as $key => $value) {
            list($tracePath, $traceInfo) = explode(':', $value);
            $trace .= '<div class="trace-item">';
            $trace .= '<span class="trace-count">' . (count($traceFragments) - $key) . '</span>';
            $trace .= '<span class="trace-info">' . $traceInfo . '</span><br>';
            $trace .= '<span class="trace-path">' . $tracePath . '</span>';
            $trace .= '</div>';
        }

        return $trace;
    }

    private function getResponseFormat(): string
    {
        return request()->expectsJson() ? 'json' : 'http';

        return 'http'; 
    }

    private function getCodePreview(string $file, int $line): ?string
    {
        if(!file_exists($file)) {
            return null;
        }

        $preview = '';
        $file = file($file);
        $line = $line;

        $start = ($line - 5 >= 0) ? $line - 5 : $line - 1;
        $end = ($line - 5 >= 0) ? $line + 4 : $line + 8;

        for ($i = $start; $i < $end; $i++) {
            if (! isset($file[$i])) {
                continue;
            }

            $text = $file[$i];

            if ($i == $line - 1) {
                $preview .= "<div class='error-line'>";
                $preview .= "<span class='line'>" . ($i + 1) . '</span>';
                $preview .= "<span class='text'>" . htmlentities($text, ENT_QUOTES) . '</span></div>';
                continue;
            }

            $preview .= "<div>";
            $preview .= "<span class='line'>" . ($i + 1) . '</span>';
            $preview .= "<span class='text'>" . htmlentities($text, ENT_QUOTES) . '</span></div>';
        }

        return $preview;
    }

    private function sendHeaders(int $statusCode)
    {
        if (!headers_sent()) {
            header("HTTP/1.1 $statusCode", true, $statusCode);

            if($this->getResponseFormat() === 'json') {
                header('Content-Type:application/json');
            } else {
                header('Content-Type:text/html');
            }
        }
    }

    private function renderCli(Throwable $exc, string $errorType): void
    {
        // print in red color in STDERR
        fwrite(STDERR, "\033[31m");
        fputs(STDERR, 'Error: ' . $exc->getMessage() . PHP_EOL);
        fputs(STDERR, 'Line: ' . $exc->getLine() . PHP_EOL);
        fputs(STDERR, 'Code: ' . $exc->getCode() . PHP_EOL);
        fputs(STDERR, 'Type: ' . $errorType . PHP_EOL);
        fputs(STDERR, 'File: ' . $exc->getFile() . PHP_EOL);
        fwrite(STDERR, PHP_EOL);
        fputs(STDERR, 'Trace: ' . PHP_EOL);
        fputs(STDERR, $exc->getTraceAsString() . PHP_EOL);
    }

    private function renderTemplate(string $errorTemplate, array $data = [])
    {
        extract($data);
        ob_start();
        require $errorTemplate;
        echo ob_get_clean();
        \flush();
        exit();
    }

    private function renderProductionTemplate(Throwable $exc)
    {
        $statusCode = $exc->getCode() ?: 500;
        $statusCode = (int) $statusCode;
        $errorTemplate = __DIR__ . '/templates/' . $this->getResponseFormat() . '/production.php';
        $message = $exc->getMessage() ?: 'We are facing some technical issues. We will be back soon.';

        if('http' == $this->getResponseFormat()) {
            if(file_exists(DIR_VIEWS . '/errors/' . $statusCode . '.php')) {
                $template = $statusCode;
                $errorTemplate = DIR_VIEWS . '/errors/layout.php';
            } 
        }

        $this->sendHeaders($statusCode);

        if($exc instanceof HttpException) {
            foreach ($exc->getHeaders() as $name => $value) {
                header("$name: $value");
            }
        }

        $this->renderTemplate($errorTemplate, [
            'code' => 'HTTP: ' . $statusCode, 
            'message' => $message,
            'template' => $template ?? null,
        ]);
    }

    private function findRelevantTrace(Throwable $exc): array
    {
        $trace = $exc->getTrace();
        $vendorPath = 'vendor/lightpack/framework/';
        
        // Look for the first file that's not in the framework
        foreach ($trace as $item) {
            if (!isset($item['file'])) {
                continue;
            }
            
            if (strpos($item['file'], $vendorPath) === false) {
                return [
                    'file' => $item['file'],
                    'line' => $item['line'],
                ];
            }
        }
        
        // If no application file found, return the original exception location
        return [
            'file' => $exc->getFile(),
            'line' => $exc->getLine(),
        ];
    }

    private function renderDevelopmentTemplate(Throwable $exc, string $errorType = 'Exception')
    {
        $errorTemplate = __DIR__ . '/templates/' . $this->getResponseFormat() . '/development.php';
        
        if($errorType === 'Error') {
            $errorType = $this->getErrorType($exc->getCode());
        }
        
        $statusCode = $exc->getCode() ?: 500;
        $statusCode = (int) $statusCode;
        $relevantTrace = $this->findRelevantTrace($exc);
        
        $data['type'] = $errorType;
        $data['code'] = $statusCode;
        $data['message'] = $exc->getMessage();
        $data['file'] = $exc->getFile();
        $data['line'] = $exc->getLine();
        $data['trace'] = $this->getTrace($exc);
        $data['format'] = $this->getResponseFormat();
        $data['environment'] = $this->environment;
        $data['code_preview'] = $this->getCodePreview($relevantTrace['file'], $relevantTrace['line']);
        $data['ex'] = $exc;

        $this->sendHeaders($statusCode);
        $this->renderTemplate($errorTemplate, $data);
    }
}