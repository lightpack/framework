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
    protected array $components = [];
    protected array $data = [];
    protected ?string $layout = 'default';

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
     * Set data for the template
     */
    public function setData(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Use a specific layout (default, minimal, or null for no layout)
     */
    public function useLayout(?string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Disable layout wrapper
     */
    public function withoutLayout(): self
    {
        $this->layout = null;
        return $this;
    }

    /**
     * Render to HTML
     */
    public function toHtml(): string
    {
        $content = $this->renderComponents();
        
        if ($this->layout === null) {
            return $content;
        }
        
        return $this->wrapInLayout($content);
    }

    /**
     * Alias for toHtml() for backward compatibility
     */
    public function render(array $data = []): string
    {
        if (!empty($data)) {
            $this->data = array_merge($this->data, $data);
        }
        
        return $this->toHtml();
    }

    /**
     * Generate plain text version from components
     */
    public function toPlainText(): string
    {
        $text = '';
        
        foreach ($this->components as $component) {
            $text .= $this->componentToPlainText($component);
        }
        
        // Clean up excessive whitespace
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }

    /**
     * Render all components to HTML
     */
    protected function renderComponents(): string
    {
        $html = '';
        
        foreach ($this->components as $component) {
            $html .= $this->renderComponent($component);
        }
        
        return $html;
    }

    /**
     * Render a single component to HTML
     */
    protected function renderComponent(array $component): string
    {
        return match ($component['type']) {
            'heading' => $this->renderHeading($component),
            'paragraph' => $this->renderParagraph($component),
            'button' => $this->renderButton($component),
            'divider' => $this->renderDivider($component),
            'alert' => $this->renderAlert($component),
            'code' => $this->renderCode($component),
            'bulletList' => $this->renderBulletList($component),
            'keyValueTable' => $this->renderKeyValueTable($component),
            default => '',
        };
    }

    /**
     * Convert a single component to plain text
     */
    protected function componentToPlainText(array $component): string
    {
        return match ($component['type']) {
            'heading' => strtoupper($component['text']) . "\n" . str_repeat('=', min(strlen($component['text']), 50)) . "\n\n",
            'paragraph' => $component['text'] . "\n\n",
            'button' => $component['text'] . ': ' . $component['url'] . "\n\n",
            'divider' => str_repeat('-', 50) . "\n\n",
            'alert' => '[' . strtoupper($component['alertType']) . '] ' . $component['text'] . "\n\n",
            'code' => $component['code'] . "\n\n",
            'bulletList' => implode("\n", array_map(fn($item) => '• ' . $item, $component['items'])) . "\n\n",
            'keyValueTable' => implode("\n", array_map(fn($k, $v) => $k . ': ' . $v, array_keys($component['data']), $component['data'])) . "\n\n",
            default => '',
        };
    }

    /**
     * Wrap content in layout
     */
    protected function wrapInLayout(string $content): string
    {
        $appName = $this->data['app_name'] ?? get_env('APP_NAME');
        $appUrl = $this->data['app_url'] ?? get_env('APP_URL');
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
     * Add a button component
     */
    public function button(string $text, string $url, string $color = 'primary'): self
    {
        $this->components[] = [
            'type' => 'button',
            'text' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
            'url' => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            'color' => $color,
        ];
        
        return $this;
    }

    /**
     * Render a button component
     */
    protected function renderButton(array $component): string
    {
        $bgColor = $this->colors[$component['color']] ?? $this->colors['primary'];

        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: {$this->spacing['lg']} 0;">
    <tr>
        <td style="border-radius: 6px; background-color: {$bgColor};">
            <a href="{$component['url']}" style="display: inline-block; padding: {$this->spacing['md']} {$this->spacing['xl']}; font-size: {$this->fonts['sizeBase']}; font-weight: 600; color: {$this->colors['white']}; text-decoration: none; border-radius: 6px;">
                {$component['text']}
            </a>
        </td>
    </tr>
</table>
HTML;
    }

    /**
     * Add a heading component
     */
    public function heading(string $text, int $level = 1): self
    {
        $this->components[] = [
            'type' => 'heading',
            'text' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
            'level' => max(1, min(3, $level)), // Clamp between 1-3
        ];
        
        return $this;
    }

    /**
     * Render a heading component
     */
    protected function renderHeading(array $component): string
    {
        $level = $component['level'];
        $sizes = [
            1 => $this->fonts['sizeH1'],
            2 => $this->fonts['sizeH2'],
            3 => $this->fonts['sizeH3'],
        ];

        $size = $sizes[$level] ?? $this->fonts['sizeH2'];
        $marginBottom = $level === 1 ? $this->spacing['lg'] : $this->spacing['md'];

        return <<<HTML
<h{$level} style="margin: 0 0 {$marginBottom}; font-size: {$size}; font-weight: 600; color: {$this->colors['text']}; line-height: 1.3;">
    {$component['text']}
</h{$level}>
HTML;
    }

    /**
     * Add a paragraph component
     */
    public function paragraph(string $text): self
    {
        $this->components[] = [
            'type' => 'paragraph',
            'text' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
        ];
        
        return $this;
    }

    /**
     * Render a paragraph component
     */
    protected function renderParagraph(array $component): string
    {
        return <<<HTML
<p style="margin: 0 0 {$this->spacing['md']}; font-size: {$this->fonts['sizeBase']}; color: {$this->colors['text']}; line-height: 1.6;">
    {$component['text']}
</p>
HTML;
    }

    /**
     * Add a divider component
     */
    public function divider(): self
    {
        $this->components[] = [
            'type' => 'divider',
        ];
        
        return $this;
    }

    /**
     * Render a divider component
     */
    protected function renderDivider(array $component): string
    {
        return <<<HTML
<hr style="margin: {$this->spacing['xl']} 0; border: none; border-top: 1px solid {$this->colors['border']};">
HTML;
    }

    /**
     * Add an alert box component
     */
    public function alert(string $text, string $type = 'info'): self
    {
        $this->components[] = [
            'type' => 'alert',
            'text' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
            'alertType' => $type,
        ];
        
        return $this;
    }

    /**
     * Render an alert box component
     */
    protected function renderAlert(array $component): string
    {
        $colors = [
            'info' => ['bg' => '#EFF6FF', 'border' => $this->colors['info'], 'text' => '#1E40AF'],
            'success' => ['bg' => '#F0FDF4', 'border' => $this->colors['success'], 'text' => '#166534'],
            'warning' => ['bg' => '#FFFBEB', 'border' => $this->colors['warning'], 'text' => '#92400E'],
            'danger' => ['bg' => '#FEF2F2', 'border' => $this->colors['danger'], 'text' => '#991B1B'],
        ];

        $style = $colors[$component['alertType']] ?? $colors['info'];

        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: {$this->spacing['lg']} 0;">
    <tr>
        <td style="padding: {$this->spacing['md']}; background-color: {$style['bg']}; border-left: 4px solid {$style['border']}; border-radius: 4px;">
            <p style="margin: 0; font-size: {$this->fonts['sizeBase']}; color: {$style['text']}; line-height: 1.6;">
                {$component['text']}
            </p>
        </td>
    </tr>
</table>
HTML;
    }

    /**
     * Add a code block component
     */
    public function code(string $code): self
    {
        $this->components[] = [
            'type' => 'code',
            'code' => htmlspecialchars($code, ENT_QUOTES, 'UTF-8'),
        ];
        
        return $this;
    }

    /**
     * Render a code block component
     */
    protected function renderCode(array $component): string
    {
        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: {$this->spacing['lg']} 0;">
    <tr>
        <td style="padding: {$this->spacing['md']}; background-color: #F3F4F6; border-radius: 4px; font-family: 'Courier New', monospace; font-size: {$this->fonts['sizeSmall']}; color: {$this->colors['text']}; overflow-x: auto;">
            <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">{$component['code']}</pre>
        </td>
    </tr>
</table>
HTML;
    }

    /**
     * Add a bullet list component
     */
    public function bulletList(array $items): self
    {
        $this->components[] = [
            'type' => 'bulletList',
            'items' => array_map(fn($item) => htmlspecialchars($item, ENT_QUOTES, 'UTF-8'), $items),
        ];
        
        return $this;
    }

    /**
     * Render a bullet list component
     */
    protected function renderBulletList(array $component): string
    {
        $content = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: ' . $this->spacing['lg'] . ' 0;">';

        foreach ($component['items'] as $item) {
            $content .= <<<HTML
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

        $content .= '</table>';
        return $content;
    }

    /**
     * Add a key-value table component
     */
    public function keyValueTable(array $data): self
    {
        $escaped = [];
        foreach ($data as $key => $value) {
            $escaped[htmlspecialchars($key, ENT_QUOTES, 'UTF-8')] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        
        $this->components[] = [
            'type' => 'keyValueTable',
            'data' => $escaped,
        ];
        
        return $this;
    }

    /**
     * Render a key-value table component
     */
    protected function renderKeyValueTable(array $component): string
    {
        $content = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: ' . $this->spacing['lg'] . ' 0; border: 1px solid ' . $this->colors['border'] . '; border-radius: 4px;">';

        $isFirst = true;
        foreach ($component['data'] as $key => $value) {
            $borderTop = $isFirst ? '' : 'border-top: 1px solid ' . $this->colors['border'] . ';';
            $content .= <<<HTML
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

        $content .= '</table>';
        return $content;
    }
}
