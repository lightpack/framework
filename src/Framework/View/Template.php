<?php

namespace Lightpack\View;

class Template
{
    private $data = [];

    /**
     * @deprecated Use render method to set data instead.
     */
    public function setData(array $data = []): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function render(string $file, array $data = []): string
    {
        $this->data = array_merge($this->data, $data);
        $file = DIR_VIEWS . '/' . $file . '.php';

        $this->throwExceptionIfTemplateNotFound($file);

        // Queue up the content in buffer.
        $output = (function ($data) {
            extract($data);
            ob_start();
            require func_get_arg(1);
            return ob_get_clean();
        })($this->data, $file);

        flush();

        return $output;
    }

    public function partial(string $file, array $data = []): string
    {
        $template = new self();

        return $template->render($file, array_merge($this->data, $data));
    }

    private function throwExceptionIfTemplateNotFound(string $template)
    {
        if (!file_exists($template)) {
            throw new \Lightpack\Exceptions\TemplateNotFoundException(
                sprintf("Error: Could not load template %s", $template)
            );
        }
    }
}
