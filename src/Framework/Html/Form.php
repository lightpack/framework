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
     * Open a form tag with multipart/form-data encoding.
     */
    public function openMultipart(string $action = '', string $method = 'POST', array $attrs = [], bool $csrf = true): string
    {
        return $this->open($action, $method, array_merge(['enctype' => 'multipart/form-data'], $attrs), $csrf);
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
        $isMultiple = isset($attrs['multiple']);
        $selectedValue = $this->resolveSelected($name, $attrs, $isMultiple);
        unset($attrs['selected']);

        $html = '<select name="' . $this->e($name) . '"';
        $html .= $this->buildAttrs($attrs);
        $html .= '>';

        foreach ($options as $val => $label) {
            if (is_array($label)) {
                $html .= '<optgroup label="' . $this->e((string) $val) . '">';
                foreach ($label as $optVal => $optLabel) {
                    $sel = $this->isSelected((string) $optVal, $selectedValue, $isMultiple);
                    $html .= '<option value="' . $this->e((string) $optVal) . '"' . ($sel ? ' selected' : '') . '>' . $this->e((string) $optLabel) . '</option>';
                }
                $html .= '</optgroup>';
            } else {
                $sel = $this->isSelected((string) $val, $selectedValue, $isMultiple);
                $html .= '<option value="' . $this->e((string) $val) . '"' . ($sel ? ' selected' : '') . '>' . $this->e((string) $label) . '</option>';
            }
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Render a multiple select dropdown.
     */
    public function selectMultiple(string $name, array $options, array $attrs = []): string
    {
        $attrs['multiple'] = true;

        return $this->select($name, $options, $attrs);
    }

    /**
     * Render a checkbox with hidden input for unchecked state.
     */
    public function checkbox(string $name, mixed $value = 1, array $attrs = []): string
    {
        $oldValue = old($this->nameToDot($name), "\0", false);

        $checked = false;
        if ($oldValue !== "\0") {
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
        $oldValue = old($this->nameToDot($name), "\0", false);

        $checked = false;
        if ($oldValue !== "\0") {
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
     * Render an email input.
     */
    public function email(string $name, array $attrs = []): string
    {
        $attrs['type'] = 'email';

        return $this->input($name, $attrs);
    }

    /**
     * Render a password input.
     */
    public function password(string $name, array $attrs = []): string
    {
        $attrs['type'] = 'password';

        return $this->input($name, $attrs);
    }

    /**
     * Render a number input.
     */
    public function number(string $name, array $attrs = []): string
    {
        $attrs['type'] = 'number';

        return $this->input($name, $attrs);
    }

    /**
     * Render a telephone input.
     */
    public function tel(string $name, array $attrs = []): string
    {
        $attrs['type'] = 'tel';

        return $this->input($name, $attrs);
    }

    /**
     * Render a URL input.
     */
    public function url(string $name, array $attrs = []): string
    {
        $attrs['type'] = 'url';

        return $this->input($name, $attrs);
    }

    /**
     * Render a date input.
     */
    public function date(string $name, array $attrs = []): string
    {
        $attrs['type'] = 'date';

        return $this->input($name, $attrs);
    }

    /**
     * Render a search input.
     */
    public function search(string $name, array $attrs = []): string
    {
        $attrs['type'] = 'search';

        return $this->input($name, $attrs);
    }

    /**
     * Render a color input.
     */
    public function color(string $name, array $attrs = []): string
    {
        $attrs['type'] = 'color';

        return $this->input($name, $attrs);
    }

    /**
     * Render a submit button.
     */
    public function submit(string $text, array $attrs = []): string
    {
        $html = '<input type="submit" value="' . $this->e($text) . '"';
        $html .= $this->buildAttrs($attrs);
        $html .= '>';

        return $html;
    }

    /**
     * Render a button element.
     */
    public function button(string $text, array $attrs = []): string
    {
        $html = '<button';
        $html .= $this->buildAttrs($attrs);
        $html .= '>' . $this->e($text) . '</button>';

        return $html;
    }

    /**
     * Render a group of checkboxes (bare tags, no wrapper).
     */
    public function checkboxes(string $name, array $options, array $attrs = []): string
    {
        $html = '';
        foreach ($options as $value => $label) {
            $html .= $this->checkbox($name, $value, $attrs) . $this->e((string) $label);
        }

        return $html;
    }

    /**
     * Render a group of radio buttons (bare tags, no wrapper).
     */
    public function radios(string $name, array $options, array $attrs = []): string
    {
        $html = '';
        foreach ($options as $value => $label) {
            $html .= $this->radio($name, $value, $attrs) . $this->e((string) $label);
        }

        return $html;
    }

    /**
     * Render a datalist for autocomplete suggestions.
     */
    public function datalist(string $id, array $options): string
    {
        $html = '<datalist id="' . $this->e($id) . '">';
        foreach ($options as $value) {
            $html .= '<option value="' . $this->e((string) $value) . '"></option>';
        }
        $html .= '</datalist>';

        return $html;
    }

    /**
     * Render a validation error message for a field.
     * Returns empty string if no error exists.
     */
    public function error(string $name): string
    {
        $message = \error($this->nameToDot($name));

        return $message !== '' ? $this->e($message) : '';
    }

    /**
     * Resolve value: old() > passed value attr > empty string.
     */
    protected function resolveValue(string $name, array &$attrs): string
    {
        $dotName = $this->nameToDot($name);
        $oldValue = old($dotName, "\0", false);

        if ($oldValue !== "\0") {
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
    protected function resolveSelected(string $name, array &$attrs, bool $multiple = false): string|array|null
    {
        $dotName = $this->nameToDot($name);
        $oldValue = old($dotName, "\0", false);

        if ($oldValue !== "\0") {
            if ($multiple && is_array($oldValue)) {
                return $oldValue;
            }

            return (string) $oldValue;
        }

        if (isset($attrs['selected'])) {
            $selected = $attrs['selected'];
            unset($attrs['selected']);
            if ($multiple && is_array($selected)) {
                return $selected;
            }

            return (string) $selected;
        }

        return null;
    }

    /**
     * Check if an option value is selected.
     */
    protected function isSelected(string $value, string|array|null $selected, bool $multiple): bool
    {
        if ($selected === null) {
            return false;
        }

        if ($multiple && is_array($selected)) {
            return in_array($value, $selected, true);
        }

        return (string) $value === (string) $selected;
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
