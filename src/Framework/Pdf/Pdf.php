<?php

namespace Lightpack\Pdf;

use Lightpack\Http\Response;
use Lightpack\View\Template;
use Lightpack\Container\Container;
use Lightpack\Pdf\Driver\DriverInterface;
use Lightpack\Storage\StorageInterface;

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
        $html = $this->template->include($view, $data);
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
     * Make the PDF downloadable by the user as an HTTP response.
     *
     * @param string $filename The filename for the downloaded PDF.
     * @return \Lightpack\Http\Response
     */
    public function download(string $filename = 'document.pdf'): Response
    {
        $content = $this->render();

        return (new Response)
            ->setType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length', strlen($content))
            ->setBody($content);
    }

    /**
     * Stream the generated PDF to the browser as an HTTP response.
     *
     * @param string $filename The filename for the streamed PDF (for Content-Disposition header).
     * @return Response
     */
    public function stream(string $filename = 'document.pdf'): Response
    {
        $callback = function () {
            echo $this->render();
        };

        return (new Response)
            ->setType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->stream($callback);
    }

    /**
     * Save the generated PDF to storage.
     *
     * @param string $path Path to save the PDF (relative to storage root)
     * @return bool True on success, false on failure
     */
    public function save(string $path): bool
    {
        /** @var Storage */
        $storage = Container::getInstance()->get('storage');
        $content = $this->render();
        
        return $storage->write($path, $content);
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
