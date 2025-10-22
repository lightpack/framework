<?php

namespace Lightpack\View;

use Throwable;

class Template
{
    protected $data = [];
    protected $embeddedTemplate = null;
    protected string $viewsPath;
    protected $layoutFile = null;
    protected $childContent = null;
    protected $stacks = [];
    protected $currentStack = null;

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
     * @deprecated Use layout() and content() instead.
     * 
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

    public function include(string $file, array $data = []): string
    {
        $mergedData = array_merge($this->data, $data);

        return $this->renderTemplateWithData($file, $mergedData);
    }

    /**
     * @deprecated Use include() instead.
     */
    public function render(string $file, array $data = []): string
    {
        return $this->include($file, $data);
    }

    public function component(string $file, array $data = []): string
    {
        return $this->renderTemplateWithData($file, $data);
    }

    /**
     * Declare a layout file for the current template.
     * The child template content will be embedded in the layout.
     */
    public function layout(string $file): void
    {
        $this->layoutFile = $file;
    }

    /**
     * Render the child template content within a layout.
     * This method is called from within layout files.
     */
    public function content(): string
    {
        return $this->childContent ?? '';
    }

    /**
     * Start pushing content to a stack.
     */
    public function push(string $stack): void
    {
        $this->currentStack = $stack;
        ob_start();
    }

    /**
     * Stop pushing content and save it to the stack.
     */
    public function endPush(): void
    {
        if ($this->currentStack === null) {
            throw new \RuntimeException('Cannot call endPush() without a corresponding push()');
        }

        $content = ob_get_clean();
        $stack = $this->currentStack;
        $this->currentStack = null;

        if (!isset($this->stacks[$stack])) {
            $this->stacks[$stack] = [];
        }

        $this->stacks[$stack][] = $content;
    }

    /**
     * Render all content that has been pushed to a stack.
     */
    public function stack(string $stack): string
    {
        if (!isset($this->stacks[$stack])) {
            return '';
        }

        return implode("\n", $this->stacks[$stack]);
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
        // Save layout state for nested renders (includes/components)
        $savedLayoutFile = $this->layoutFile;
        $this->layoutFile = null;
        
        $obLevel = ob_get_level();
        ob_start();

        try {
            $this->requireTemplate($file, $data);
        } catch (Throwable $e) {
            $this->catchViewExceptions($e, $obLevel);
        }

        $output = ob_get_clean();

        // Check if this template declared a layout
        $declaredLayout = $this->layoutFile;
        
        // Restore saved layout state
        $this->layoutFile = $savedLayoutFile;

        // If a layout was declared, render it with the current output as content
        if ($declaredLayout) {
            $this->childContent = $output;
            return $this->renderTemplateWithData($declaredLayout, $data);
        }

        return $output;
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
