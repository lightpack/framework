<?php

namespace Lightpack\Mail;

/**
 * MailTemplate - email templating building blocks
 * 
 * Features:
 * - Table-based layouts for maximum email client compatibility
 * - Automatic CSS inlining
 * - Component system for reusable parts
 * - Beautiful default styling
 * - Responsive design (where supported)
 * - Plain text auto-generation
 */
class MailTemplate
{
    protected array $data = [];
    protected bool $inlineCss = true;

    // Default color scheme
    protected array $colors = [
        'primary' => '#4F46E5',      // Indigo
        'secondary' => '#6B7280',    // Gray
        'success' => '#10B981',      // Green
        'danger' => '#EF4444',       // Red
        'warning' => '#F59E0B',      // Amber
        'info' => '#3B82F6',         // Blue
        'text' => '#1F2937',         // Dark gray
        'textLight' => '#6B7280',    // Medium gray
        'background' => '#F9FAFB',   // Light gray
        'white' => '#FFFFFF',
        'border' => '#E5E7EB',       // Light border
    ];

    // Typography settings
    protected array $fonts = [
        'family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
        'sizeBase' => '16px',
        'sizeSmall' => '14px',
        'sizeLarge' => '18px',
        'sizeH1' => '32px',
        'sizeH2' => '24px',
        'sizeH3' => '20px',
    ];

    // Spacing settings
    protected array $spacing = [
        'xs' => '4px',
        'sm' => '8px',
        'md' => '16px',
        'lg' => '24px',
        'xl' => '32px',
        'xxl' => '48px',
    ];

    public function __construct(array $config = [])
    {
        if (!empty($config['colors'])) {
            $this->colors = array_merge($this->colors, $config['colors']);
        }

        if (!empty($config['fonts'])) {
            $this->fonts = array_merge($this->fonts, $config['fonts']);
        }

        if (!empty($config['spacing'])) {
            $this->spacing = array_merge($this->spacing, $config['spacing']);
        }
    }

    /**
     * Set custom colors
     */
    public function setColors(array $colors): self
    {
        $this->colors = array_merge($this->colors, $colors);
        return $this;
    }

    /**
     * Generate plain text version from HTML
     */
    public function toPlainText(string $html): string
    {
        // Remove script and style tags
        $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $text);

        // Convert common HTML elements to text equivalents
        $text = preg_replace('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', "\n\n" . '$1' . "\n", $text);
        $text = preg_replace('/<p[^>]*>(.*?)<\/p>/is', '$1' . "\n\n", $text);
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<hr\s*\/?>/i', "\n" . str_repeat('-', 50) . "\n", $text);

        // Convert links
        $text = preg_replace('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2 ($1)', $text);

        // Convert lists
        $text = preg_replace('/<li[^>]*>(.*?)<\/li>/is', '• $1' . "\n", $text);
        $text = preg_replace('/<\/?[ou]l[^>]*>/i', "\n", $text);

        // Remove remaining HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Clean up whitespace
        $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Wrap content in layout
     */
    protected function wrapInLayout(string $content): string
    {
        $appName = $this->data['app_name'] ?? get_env('APP_NAME', 'Lightpack PHP Framework');
        $appUrl = $this->data['app_url'] ?? get_env('APP_URL', '');
        $year = date('Y');
        $subject = $this->data['subject'] ?? '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{$subject}</title>
    <!--[if mso]>
    <style type="text/css">
        table {border-collapse: collapse;}
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: {$this->colors['background']}; font-family: {$this->fonts['family']};">
    <!-- Wrapper table for Outlook -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: {$this->colors['background']};">
        <tr>
            <td style="padding: {$this->spacing['lg']} 0;">
                <!-- Main container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" align="center" style="margin: 0 auto; background-color: {$this->colors['white']}; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: {$this->spacing['xl']} {$this->spacing['xl']} {$this->spacing['lg']}; text-align: center; border-bottom: 1px solid {$this->colors['border']};">
                            <h1 style="margin: 0; font-size: {$this->fonts['sizeH2']}; font-weight: 600; color: {$this->colors['text']};">
                                {$appName}
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: {$this->spacing['xl']};">
                            {$content}
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: {$this->spacing['lg']} {$this->spacing['xl']}; text-align: center; border-top: 1px solid {$this->colors['border']}; background-color: {$this->colors['background']};">
                            <p style="margin: 0 0 {$this->spacing['sm']}; font-size: {$this->fonts['sizeSmall']}; color: {$this->colors['textLight']};">
                                &copy; {$year} {$appName}. All rights reserved.
                            </p>
                            {$this->renderFooterLinks()}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Render footer links
     */
    protected function renderFooterLinks(): string
    {
        $links = $this->data['footer_links'] ?? [];

        if (empty($links)) {
            return '';
        }

        $html = '<p style="margin: 0; font-size: ' . $this->fonts['sizeSmall'] . '; color: ' . $this->colors['textLight'] . ';">';
        $linkParts = [];

        foreach ($links as $text => $url) {
            $linkParts[] = '<a href="' . htmlspecialchars($url) . '" style="color: ' . $this->colors['primary'] . '; text-decoration: none;">' . htmlspecialchars($text) . '</a>';
        }

        $html .= implode(' • ', $linkParts);
        $html .= '</p>';

        return $html;
    }

    /**
     * Render a button component
     */
    public function button(string $text, string $url, string $color = 'primary'): string
    {
        $bgColor = $this->colors[$color] ?? $this->colors['primary'];

        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: {$this->spacing['lg']} 0;">
    <tr>
        <td style="border-radius: 6px; background-color: {$bgColor};">
            <a href="{$url}" style="display: inline-block; padding: {$this->spacing['md']} {$this->spacing['xl']}; font-size: {$this->fonts['sizeBase']}; font-weight: 600; color: {$this->colors['white']}; text-decoration: none; border-radius: 6px;">
                {$text}
            </a>
        </td>
    </tr>
</table>
HTML;
    }

    /**
     * Render a heading
     */
    public function heading(string $text, int $level = 1): string
    {
        $sizes = [
            1 => $this->fonts['sizeH1'],
            2 => $this->fonts['sizeH2'],
            3 => $this->fonts['sizeH3'],
        ];

        $size = $sizes[$level] ?? $this->fonts['sizeH2'];
        $marginBottom = $level === 1 ? $this->spacing['lg'] : $this->spacing['md'];

        return <<<HTML
<h{$level} style="margin: 0 0 {$marginBottom}; font-size: {$size}; font-weight: 600; color: {$this->colors['text']}; line-height: 1.3;">
    {$text}
</h{$level}>
HTML;
    }

    /**
     * Render a paragraph
     */
    public function paragraph(string $text): string
    {
        return <<<HTML
<p style="margin: 0 0 {$this->spacing['md']}; font-size: {$this->fonts['sizeBase']}; color: {$this->colors['text']}; line-height: 1.6;">
    {$text}
</p>
HTML;
    }

    /**
     * Render a divider
     */
    public function divider(): string
    {
        return <<<HTML
<hr style="margin: {$this->spacing['xl']} 0; border: none; border-top: 1px solid {$this->colors['border']};">
HTML;
    }

    /**
     * Render an alert box
     */
    public function alert(string $text, string $type = 'info'): string
    {
        $colors = [
            'info' => ['bg' => '#EFF6FF', 'border' => $this->colors['info'], 'text' => '#1E40AF'],
            'success' => ['bg' => '#F0FDF4', 'border' => $this->colors['success'], 'text' => '#166534'],
            'warning' => ['bg' => '#FFFBEB', 'border' => $this->colors['warning'], 'text' => '#92400E'],
            'danger' => ['bg' => '#FEF2F2', 'border' => $this->colors['danger'], 'text' => '#991B1B'],
        ];

        $style = $colors[$type] ?? $colors['info'];

        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: {$this->spacing['lg']} 0;">
    <tr>
        <td style="padding: {$this->spacing['md']}; background-color: {$style['bg']}; border-left: 4px solid {$style['border']}; border-radius: 4px;">
            <p style="margin: 0; font-size: {$this->fonts['sizeBase']}; color: {$style['text']}; line-height: 1.6;">
                {$text}
            </p>
        </td>
    </tr>
</table>
HTML;
    }

    /**
     * Render a code block
     */
    public function code(string $code): string
    {
        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: {$this->spacing['lg']} 0;">
    <tr>
        <td style="padding: {$this->spacing['md']}; background-color: #F3F4F6; border-radius: 4px; font-family: 'Courier New', monospace; font-size: {$this->fonts['sizeSmall']}; color: {$this->colors['text']}; overflow-x: auto;">
            <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">{$code}</pre>
        </td>
    </tr>
</table>
HTML;
    }

    /**
     * Render a list
     */
    public function bulletList(array $items): string
    {
        $html = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: ' . $this->spacing['lg'] . ' 0;">';

        foreach ($items as $item) {
            $html .= <<<HTML
    <tr>
        <td style="padding: {$this->spacing['sm']} 0;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="padding-right: {$this->spacing['sm']}; vertical-align: top; color: {$this->colors['primary']}; font-weight: bold;">•</td>
                    <td style="font-size: {$this->fonts['sizeBase']}; color: {$this->colors['text']}; line-height: 1.6;">{$item}</td>
                </tr>
            </table>
        </td>
    </tr>
HTML;
        }

        $html .= '</table>';
        return $html;
    }

    /**
     * Render a key-value table
     */
    public function keyValueTable(array $data): string
    {
        $html = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: ' . $this->spacing['lg'] . ' 0; border: 1px solid ' . $this->colors['border'] . '; border-radius: 4px;">';

        $isFirst = true;
        foreach ($data as $key => $value) {
            $borderTop = $isFirst ? '' : 'border-top: 1px solid ' . $this->colors['border'] . ';';
            $html .= <<<HTML
    <tr>
        <td style="padding: {$this->spacing['md']}; {$borderTop} font-weight: 600; color: {$this->colors['text']}; width: 40%;">
            {$key}
        </td>
        <td style="padding: {$this->spacing['md']}; {$borderTop} color: {$this->colors['text']};">
            {$value}
        </td>
    </tr>
HTML;
            $isFirst = false;
        }

        $html .= '</table>';
        return $html;
    }
}
