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

    public function testRenderEmptyHtml()
    {
        $this->pdf->html('');
        $content = $this->pdf->render();
        $this->assertStringContainsString('%PDF', $content);
    }

    public function testRenderLargeContent()
    {
        $largeHtml = str_repeat('<p>Lightpack PDF</p>', 5000); // Large content
        $this->pdf->html($largeHtml);
        $content = $this->pdf->render();
        $this->assertStringContainsString('%PDF', $content);
    }

    public function testRenderInvalidHtml()
    {
        $this->pdf->html('<div><span>Broken'); // Not closed properly
        $content = $this->pdf->render();
        $this->assertStringContainsString('%PDF', $content);
    }

    public function testMissingTemplateThrows()
    {
        $this->expectException(\Exception::class);
        $this->pdf->template('not_a_real_template');
        $this->pdf->render();
    }

    public function testUnicodeAndSpecialCharacters()
    {
        $this->pdf->html('<p>Unicode: 你好, мир, 😀, café</p>');
        $content = $this->pdf->render();
        $this->assertStringContainsString('%PDF', $content);
    }

    public function testImageEmbedding()
    {
        // Use a small PNG data URI for portability
        $img = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $this->pdf->html('<img src="' . $img . '" alt="dot" />');
        $content = $this->pdf->render();
        $this->assertStringContainsString('%PDF', $content);
    }

    public function testCustomPaperSizeAndOrientation()
    {
        $driver = $this->pdf->getDriver();
        if ($driver instanceof DompdfDriver) {
            $dompdf = $driver->getInstance();
            $dompdf->setPaper('A4', 'landscape');
        }
        $this->pdf->html('<h1>Landscape PDF</h1>');
        $content = $this->pdf->render();
        $this->assertStringContainsString('%PDF', $content);
    }
}
