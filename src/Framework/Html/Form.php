<?php

namespace Lightpack\Html;

class Form
{
    /**
     * Open a form tag with CSRF and method spoofing support.
     */
    public function open(string $action = '', string $method = 'POST', array $attrs = [], bool $csrf = true): string
    {
        return form_open($action, $method, $attrs, $csrf);
    }

    /**
     * Close a form tag.
     */
    public function close(): string
    {
        return form_close();
    }

    /**
     * Render a text input (or any input type via $attrs['type']).
     */
    public function input(string $name, array $attrs = []): string
    {
        $type = $attrs['type'] ?? 'text';
        unset($attrs['type']);

        $value = $this->resolveValue($name, $attrs);
        unset($attrs['value']);

        $html = '<input type="' . $this->e($type) . '" name="' . $this->e($name) . '"';
        $html .= ' value="' . $this->e($value) . '"';
        $html .= $this->buildAttrs($attrs);
        $html .= '>';

        return $html;
    }

    /**
     * Render a textarea.
     */
    public function textarea(string $name, array $attrs = []): string
    {
        $value = $this->resolveValue($name, $attrs);
        unset($attrs['value']);

        $html = '<textarea name="' . $this->e($name) . '"';
        $html .= $this->buildAttrs($attrs);
        $html .= '>' . $this->e($value) . '</textarea>';

        return $html;
    }

    /**
     * Render a select dropdown.
     */
    public function select(string $name, array $options, array $attrs = []): string
    {
        $selectedValue = $this->resolveSelected($name, $attrs);
        unset($attrs['selected']);

        $html = '<select name="' . $this->e($name) . '"';
        $html .= $this->buildAttrs($attrs);
        $html .= '>';

        foreach ($options as $val => $label) {
            $isSelected = (string) $val === (string) $selectedValue;
            $html .= '<option value="' . $this->e((string) $val) . '"' . ($isSelected ? ' selected' : '') . '>' . $this->e((string) $label) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Render a checkbox with hidden input for unchecked state.
     */
    public function checkbox(string $name, mixed $value = 1, array $attrs = []): string
    {
        $oldValue = old($this->nameToDot($name), null, false);

        $checked = false;
        if ($oldValue !== null) {
            $checked = (string) $oldValue === (string) $value;
        } elseif (isset($attrs['checked'])) {
            $checked = (bool) $attrs['checked'];
        }
        unset($attrs['checked']);

        $html = '<input type="hidden" name="' . $this->e($name) . '" value="">';
        $html .= '<input type="checkbox" name="' . $this->e($name) . '" value="' . $this->e((string) $value) . '"';
        if ($checked) {
            $html .= ' checked';
        }
        $html .= $this->buildAttrs($attrs);
        $html .= '>';

        return $html;
    }

    /**
     * Render a radio button.
     */
    public function radio(string $name, mixed $value, array $attrs = []): string
    {
        $oldValue = old($this->nameToDot($name), null, false);

        $checked = false;
        if ($oldValue !== null) {
            $checked = (string) $oldValue === (string) $value;
        } elseif (isset($attrs['checked'])) {
            $checked = (bool) $attrs['checked'];
        }
        unset($attrs['checked']);

        $html = '<input type="radio" name="' . $this->e($name) . '" value="' . $this->e((string) $value) . '"';
        if ($checked) {
            $html .= ' checked';
        }
        $html .= $this->buildAttrs($attrs);
        $html .= '>';

        return $html;
    }

    /**
     * Render a file input. Never repopulates values.
     */
    public function file(string $name, array $attrs = []): string
    {
        unset($attrs['value']);

        $html = '<input type="file" name="' . $this->e($name) . '"';
        $html .= $this->buildAttrs($attrs);
        $html .= '>';

        return $html;
    }

    /**
     * Render a hidden input.
     */
    public function hidden(string $name, ?string $value = null, array $attrs = []): string
    {
        unset($attrs['value']);

        $html = '<input type="hidden" name="' . $this->e($name) . '"';

        if ($value !== null) {
            $html .= ' value="' . $this->e($value) . '"';
        }

        $html .= $this->buildAttrs($attrs);
        $html .= '>';

        return $html;
    }

    /**
     * Render a label tag.
     */
    public function label(string $text, string $for = '', array $attrs = []): string
    {
        $html = '<label';
        if ($for !== '') {
            $html .= ' for="' . $this->e($for) . '"';
        }
        $html .= $this->buildAttrs($attrs);
        $html .= '>' . $this->e($text) . '</label>';

        return $html;
    }

    /**
     * Resolve value: old() > passed value attr > empty string.
     */
    protected function resolveValue(string $name, array &$attrs): string
    {
        $dotName = $this->nameToDot($name);
        $oldValue = old($dotName, null, false);

        if ($oldValue !== null) {
            return (string) $oldValue;
        }

        if (isset($attrs['value'])) {
            $value = (string) $attrs['value'];
            unset($attrs['value']);
            return $value;
        }

        return '';
    }

    /**
     * Resolve selected value for selects: old() > passed selected attr > null.
     */
    protected function resolveSelected(string $name, array &$attrs): ?string
    {
        $dotName = $this->nameToDot($name);
        $oldValue = old($dotName, null, false);

        if ($oldValue !== null) {
            return (string) $oldValue;
        }

        if (isset($attrs['selected'])) {
            $selected = (string) $attrs['selected'];
            unset($attrs['selected']);
            return $selected;
        }

        return null;
    }

    /**
     * Convert bracket notation to dot notation for old()/error().
     */
    protected function nameToDot(string $name): string
    {
        $dot = str_replace(['[', ']'], ['.', ''], $name);

        return rtrim($dot, '.');
    }

    /**
     * Build HTML attributes string from associative array.
     */
    protected function buildAttrs(array $attrs): string
    {
        $html = '';

        foreach ($attrs as $key => $val) {
            if ($val === null || $val === false) {
                continue;
            }

            if ($val === true) {
                $html .= ' ' . $this->e($key);
                continue;
            }

            $html .= ' ' . $this->e((string) $key) . '="' . $this->e((string) $val) . '"';
        }

        return $html;
    }

    /**
     * Escape HTML entities.
     */
    protected function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
