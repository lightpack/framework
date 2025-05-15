<?php

namespace Tests\Pdf;

use Lightpack\Pdf\Pdf;
use Lightpack\Pdf\Driver\DompdfDriver;
use Lightpack\View\Template;
use PHPUnit\Framework\TestCase;

class PdfTest extends TestCase
{
    protected Pdf $pdf;

    protected function setUp(): void
    {
        if (!defined('DIR_VIEWS')) {
            define('DIR_VIEWS', __DIR__ . '/fixtures');
        }
        $template = new Template(__DIR__ . '/fixtures');
        $driver = new DompdfDriver([]);
        $this->pdf = new Pdf($driver, $template);
    }

    public function testCanSetMeta()
    {
        $this->pdf->setMeta([
            'title' => 'Test PDF',
            'author' => 'Lightpack',
            'subject' => 'Testing',
            'keywords' => 'pdf, test, lightpack',
        ]);
        // No exception means pass; deeper test would require PDF inspection
        $this->assertTrue(true);
    }

    public function testCanSetTitleAndAuthor()
    {
        $this->pdf->setTitle('My Title');
        $this->pdf->setAuthor('Author Name');
        $this->assertTrue(true);
    }

    public function testCanWriteHtml()
    {
        $this->pdf->html('<h1>Hello</h1>');
        $content = $this->pdf->render();
        $this->assertStringContainsString('%PDF', $content);
    }

    public function testCanRenderTemplate()
    {
        $this->pdf->template('testview', ['title' => 'From Template']);
        $content = $this->pdf->render();
        $this->assertStringContainsString('%PDF', $content);
    }
}
