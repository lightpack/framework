<?php

namespace Lightpack\View;

use Throwable;

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

        try {
            $level = ob_get_level();
            ob_start();

            (function () {
                extract($this->data);
                require func_get_arg(0);
            })($file);

            return ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    public function include(string $file, array $data = []): string
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
