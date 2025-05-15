<?php

namespace Tests\Pdf;

use Lightpack\Container\Container;
use Lightpack\Pdf\Pdf;
use Lightpack\Pdf\Driver\DompdfDriver;
use Lightpack\Storage\LocalStorage;
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

    public function testDownloadReturnsPdfResponse()
    {
        $this->pdf->html('<h1>Download PDF</h1>');
        $response = $this->pdf->download('test-download.pdf');
        $this->assertInstanceOf(\Lightpack\Http\Response::class, $response);
        $this->assertEquals('application/pdf', $response->getType());
        $this->assertStringContainsString('attachment; filename="test-download.pdf"', $response->getHeader('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getBody());
    }

    public function testStreamReturnsPdfResponse()
    {
        $this->pdf->html('<h1>Stream PDF</h1>');
        $response = $this->pdf->stream('test-stream.pdf');
        $this->assertInstanceOf(\Lightpack\Http\Response::class, $response);
        $this->assertEquals('application/pdf', $response->getType());
        $this->assertStringContainsString('inline; filename="test-stream.pdf"', $response->getHeader('Content-Disposition'));
        $this->assertIsCallable($response->getStreamCallback());

        // test the callback output
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $output);
    }

    public function testSaveWritesPdfToStorage()
    {
        Container::getInstance()->register('storage', function() {
            return new LocalStorage(__DIR__);
        });

        $this->pdf->html('<h1>Save PDF</h1>');
        $path = 'test-save.pdf';
        $result = $this->pdf->save($path);
        $this->assertTrue($result);

        $storage = Container::getInstance()->get('storage');
        $this->assertTrue($storage->exists($path));
        $content = $storage->read($path);
        $this->assertStringStartsWith('%PDF', $content);
        // Cleanup
        $storage->delete($path);
    }
}
