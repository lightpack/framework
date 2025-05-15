<?php
namespace Lightpack\Pdf\Driver;

use Dompdf\Dompdf;
use Dompdf\Options;

class DompdfDriver implements DriverInterface
{
    protected Dompdf $dompdf;
    protected array $meta = [];

    public function __construct(array $options = [])
    {
        $dompdfOptions = new Options();
        foreach ($options as $key => $value) {
            $dompdfOptions->set($key, $value);
        }
        $this->dompdf = new Dompdf($dompdfOptions);
    }

    public function loadHtml(string $html): void
    {
        $this->dompdf->loadHtml($html);
    }

    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }

    public function addPage(): void
    {
        // Dompdf does not support explicit addPage, so use a page-break in HTML
        $this->dompdf->loadHtml('<div style="page-break-after:always;"></div>');
    }

    public function render(): string
    {
        $this->applyMeta();
        $this->dompdf->render();
        return $this->dompdf->output();
    }


    protected function applyMeta()
    {
        if (isset($this->meta['title'])) {
            $this->dompdf->addInfo('Title', $this->meta['title']);
        }
        if (isset($this->meta['author'])) {
            $this->dompdf->addInfo('Author', $this->meta['author']);
        }
        if (isset($this->meta['subject'])) {
            $this->dompdf->addInfo('Subject', $this->meta['subject']);
        }
        if (isset($this->meta['keywords'])) {
            $this->dompdf->addInfo('Keywords', $this->meta['keywords']);
        }
    }

    public function getInstance(): object
    {
        return $this->dompdf;
    }
}
