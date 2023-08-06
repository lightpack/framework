<?php

namespace Lightpack\View;

use Throwable;

class Template
{
    protected $data = [];

    public function setData(array $data = []): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function render(string $file, array $data = []): string
    {
        $mergedData = array_merge($this->data, $data);

        return $this->renderTemplateWithData($file, $mergedData);
    }

    public function include(string $file, array $data = []): string
    {
        $template = new self;

        $template->setData(array_merge($this->data, $data));

        return $template->render($file);
    }

    public function component(string $file, array $data): string
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
        $template = DIR_VIEWS . '/' . $file . '.php';

        $this->throwExceptionIfTemplateNotFound($template);

        return $template;
    }
}
