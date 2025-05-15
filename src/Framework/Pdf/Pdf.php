<?php
namespace Lightpack\Pdf;

use Lightpack\View\Template;
use Lightpack\Pdf\Driver\DriverInterface;

/**
 * Lightpack PDF Service
 *
 * Provides a driver-based, extensible API for generating PDF documents in Lightpack applications.
 * Supports rendering HTML or templates to PDF, setting document metadata, and advanced driver access.
 *
 * Example usage:
 *   $pdf = $container->get('pdf');
 *   $pdf->setTitle('Invoice')->html('<h1>Hello</h1>')->output('invoice.pdf', 'D');
 *
 * @package Lightpack\Pdf
 */
class Pdf
{
    protected $meta = [];
    protected $html = '';

    /**
     * Construct a new PDF service instance.
     *
     * @param DriverInterface $driver   The PDF driver implementation (e.g., DompdfDriver).
     * @param Template $template        The Lightpack template renderer for view-based PDF generation.
     * @param array $options            Optional driver options.
     */
    public function __construct(
        protected DriverInterface $driver, 
        protected Template $template, 
        protected array $options = []
    ) {}

    /**
     * Set multiple metadata fields (title, author, subject, keywords) for the PDF document.
     *
     * @param array $meta
     * @return self
     */
    public function setMeta(array $meta): self
    {
        $this->meta = $meta;
        $this->driver->setMeta($meta);
        return $this;
    }

    /**
     * Set the document title metadata.
     *
     * @param string $title
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->meta['title'] = $title;
        $this->driver->setMeta($this->meta);
        return $this;
    }

    /**
     * Set the document author metadata.
     *
     * @param string $author
     * @return self
     */
    public function setAuthor(string $author): self
    {
        $this->meta['author'] = $author;
        $this->driver->setMeta($this->meta);
        return $this;
    }

    /**
     * Add a new page to the PDF document.
     *
     * @return self
     */
    public function addPage(): self
    {
        $this->driver->addPage();
        return $this;
    }

    /**
     * Append raw HTML content to the PDF document.
     *
     * @param string $html
     * @return self
     */
    public function html(string $html): self
    {
        $this->html .= $html;
        $this->driver->loadHtml($this->html);
        return $this;
    }

    /**
     * Render a Lightpack view template to HTML and append it to the PDF document.
     *
     * @param string $view   Template name (relative to views directory)
     * @param array $data    Data to pass to the view
     * @return self
     */
    public function template(string $view, array $data = []): self
    {
        $html = $this->template->render($view, $data);
        return $this->html($html);
    }

    /**
     * Render the PDF and return the raw PDF content as a string.
     *
     * @return string
     */
    public function render(): string
    {
        return $this->driver->render();
    }

    /**
     * Output the PDF to the browser, download, file, or as a string.
     *
     * @param string|null $filename   Output filename (optional)
     * @param string $dest            Output destination: 'I' (inline), 'D' (download), 'F' (file), 'S' (string)
     * @return mixed                  Output depends on destination
     */
    public function output(?string $filename = null, string $dest = 'I')
    {
        return $this->driver->output($filename, $dest);
    }

    /**
     * Get the underlying PDF driver instance (implements DriverInterface).
     * Useful for driver-specific advanced usage.
     *
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * Get the raw PDF engine instance from the driver (e.g., Dompdf, mPDF).
     * Use for advanced/custom driver-specific features.
     *
     * @return object
     */
    public function getDriverInstance(): object
    {
        return $this->driver->getInstance();
    }
}
