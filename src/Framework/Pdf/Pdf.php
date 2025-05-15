<?php
namespace Lightpack\Pdf;

use Lightpack\View\Template;
use Lightpack\Pdf\Driver\DriverInterface;

class Pdf
{
    protected $meta = [];
    protected $html = '';

    public function __construct(
        protected DriverInterface $driver, 
        protected Template $template, 
        protected array $options = []
    ) {}

    public function setMeta(array $meta): self
    {
        $this->meta = $meta;
        $this->driver->setMeta($meta);
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->meta['title'] = $title;
        $this->driver->setMeta($this->meta);
        return $this;
    }

    public function setAuthor(string $author): self
    {
        $this->meta['author'] = $author;
        $this->driver->setMeta($this->meta);
        return $this;
    }

    public function addPage(): self
    {
        $this->driver->addPage();
        return $this;
    }

    public function html(string $html): self
    {
        $this->html .= $html;
        $this->driver->loadHtml($this->html);
        return $this;
    }

    public function template(string $view, array $data = []): self
    {
        $html = $this->template->render($view, $data);
        return $this->html($html);
    }

    public function render(): string
    {
        return $this->driver->render();
    }

    public function output(?string $filename = null, string $dest = 'I')
    {
        return $this->driver->output($filename, $dest);
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    public function getDriverInstance(): object
    {
        return $this->driver->getInstance();
    }
}
