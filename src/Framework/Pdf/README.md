# Lightpack PDF Service

A robust, extensible PDF generation API for Lightpack applications, featuring driver-based architecture, seamless storage integration, and full HTTP response support.

---

## Features
- **Driver-based architecture** (Dompdf supported, extensible)
- **Render HTML or templates to PDF**
- **Set metadata** (title, author, subject, keywords)
- **Add pages, embed images, Unicode support**
- **Output as download, stream, or save to storage**
- **Advanced driver access for custom features**
- **Fully tested with PHPUnit**

---

## Quick Start

### 1. Install Dompdf

```
composer require dompdf/dompdf
```

### 2. Configure PDF Provider

In your Lightpack config:
```php
'pdf' => [
    'driver' => 'dompdf',
    'dompdf' => [
        // Dompdf options (optional)
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true,
    ],
],
```

### 3. Basic Usage

```php
$pdf = app('pdf');
$pdf->setTitle('Invoice')
    ->setAuthor('Lightpack')
    ->html('<h1>Invoice #123</h1>');

// Download
return $pdf->download('invoice.pdf');

// Stream inline
return $pdf->stream('invoice.pdf');

// Save to storage
$pdf->save('invoices/invoice-123.pdf');
```

---

## API Reference

### Set Metadata
```php
$pdf->setMeta([
    'title' => 'Report',
    'author' => 'Admin',
    'subject' => 'Monthly',
    'keywords' => 'report, monthly, pdf',
]);
```

### Render from HTML string or view template
```php
$pdf->html('<h1>Hello</h1>');
$pdf->template('invoice', ['order' => $order]);
```

### Add Pages
```php
$pdf->addPage();
```

### Download as Attachment
```php
return $pdf->download('myfile.pdf');
```

### Stream Inline
```php
return $pdf->stream('myfile.pdf');
```

### Save to Storage
```php
$pdf->save('reports/2025-05-15/report.pdf');
```

### Advanced: Access Driver Instance
```php
$driver = $pdf->getDriver();
$dompdf = $driver->getInstance();
$dompdf->setPaper('A4', 'landscape');
```

---

## Storage Integration
- The `save()` method uses the Lightpack `storage` service.
- Works with local disk, S3, or any custom driver.
- Example:
    ```php
    $pdf->save('public/invoices/invoice-123.pdf');
    $url = app('storage')->url('public/invoices/invoice-123.pdf');
    ```

---

## Advanced Usage & Extensibility

- **Add new drivers** by implementing `Lightpack\Pdf\Driver\DriverInterface`.
- **Access raw driver** for custom Dompdf/mPDF/TCPDF features.
- **Customize storage** by binding your own storage implementation.

---

## Example: Full Controller

```php
public function invoice($id)
{
    $order = ...; // Fetch order
    $pdf = app('pdf');
    $pdf->template('invoice', ['order' => $order]);
    return $pdf->download('invoice-' . $id . '.pdf');
}
```

---

## FAQ

**Q: How do I add images?**
- Use standard HTML `<img src="...">` tags. For local files, enable Dompdf's remote option and use absolute paths or data URIs.

**Q: Can I use S3 or other storage?**
- Yes, configure the `storage` service for S3 or any custom driver.

**Q: How do I set custom paper size/orientation?**
- Access the driver: `$pdf->getDriverInstance()->setPaper('A4', 'landscape');`

**Q: How do I test PDF output?**
- See `tests/Pdf/PdfTest.php` for real-world test examples.

---