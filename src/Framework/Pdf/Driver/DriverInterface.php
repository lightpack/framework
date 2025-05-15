<?php
namespace Lightpack\Pdf\Driver;

interface DriverInterface
{
    /**
     * Load HTML content to be rendered into PDF.
     * @param string $html
     * @return void
     */
    public function loadHtml(string $html): void;

    /**
     * Set PDF document metadata (title, author, etc).
     * @param array $meta
     * @return void
     */
    public function setMeta(array $meta): void;

    /**
     * Add a new page to the PDF.
     * @return void
     */
    public function addPage(): void;

    /**
     * Render the PDF to string (raw binary output).
     * @return string
     */
    public function render(): string;

    /**
     * Get the underlying PDF driver instance for advanced usage.
     * @return object
     */
    public function getInstance(): object;
}
