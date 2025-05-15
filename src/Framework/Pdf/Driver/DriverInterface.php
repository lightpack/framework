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
     * Output the PDF to browser or file.
     * @param string|null $filename
     * @param string $dest ("I"=inline, "D"=download, "F"=file, "S"=string)
     * @return void|string
     */
    public function output(?string $filename = null, string $dest = 'I');
}
