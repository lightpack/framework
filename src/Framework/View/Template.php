<?php

namespace Lightpack\View;

use Throwable;

class Template
{
    protected $data = [];
    protected $embeddedTemplate = null;
    protected string $viewsPath;

    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?? (defined('DIR_VIEWS') ? DIR_VIEWS : '');
    }

    public function setData(array $data = []): self
    {
        $this->data = $data;

        if (isset($data['__embed'])) {
            $this->embeddedTemplate = $data['__embed'];
            unset($this->data['__embed']); // Do not expose to templates
        }

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Render the embedded child template if __embed is set, 
     * else return empty string.
     */
    public function embed(): string
    {
        if (!$this->embeddedTemplate) {
            return '';
        }
        
        $template = $this->embeddedTemplate;
        $this->embeddedTemplate = null; // Prevent recursion
        
        // Render embedded template directly with current data (no merge needed)
        return $this->renderTemplateWithData($template, $this->data);
    }

    public function render(string $file, array $data = []): string
    {
        $mergedData = array_merge($this->data, $data);

        return $this->renderTemplateWithData($file, $mergedData);
    }

    public function include(string $file, array $data = []): string
    {
        // Semantic alias for render() - used for including partials
        return $this->render($file, $data);
    }

    public function includeIf(bool $flag, string $file, array $data = []): string
    {
        if ($flag) {
            return $this->include($file, $data);
        }

        return '';
    }

    public function component(string $file, array $data = []): string
    {
        return $this->renderTemplateWithData($file, $data);
    }

    protected function throwExceptionIfTemplateNotFound(string $template)
    {
        if (!file_exists($template)) {
            throw new \Lightpack\Exceptions\TemplateNotFoundException(
                sprintf("Error: Could not load template %s", $template)
            );
        }
    }

    protected function catchViewExceptions(Throwable $e, int $obLevel)
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }

    protected function renderTemplateWithData(string $file, array $data): string
    {
        $obLevel = ob_get_level();
        ob_start();

        try {
            $this->requireTemplate($file, $data);
        } catch (Throwable $e) {
            $this->catchViewExceptions($e, $obLevel);
        }

        return ob_get_clean();
    }

    protected function requireTemplate(string $file, array $data)
    {
        $template = $this->resolveTemplatePath($file);

        return (function () {
            extract(func_get_arg(1));
            require func_get_arg(0);
        })($template, $data);
    }

    protected function resolveTemplatePath(string $file): string
    {
        $template = $this->viewsPath . '/' . $file . '.php';

        $this->throwExceptionIfTemplateNotFound($template);

        return $template;
    }
}
