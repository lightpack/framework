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

        return $this->openTag('input', array_merge([
            'type' => $type,
            'name' => $name,
            'value' => $value,
        ], $attrs));
    }

    /**
     * Render a textarea.
     */
    public function textarea(string $name, array $attrs = []): string
    {
        $value = $this->resolveValue($name, $attrs);
        unset($attrs['value']);

        return $this->tag('textarea', $value, array_merge(['name' => $name], $attrs));
    }

    /**
     * Render a select dropdown.
     */
    public function select(string $name, array $options, array $attrs = []): string
    {
        $isMultiple = isset($attrs['multiple']);
        $selectedValue = $this->resolveSelected($name, $attrs, $isMultiple);
        unset($attrs['selected']);

        $html = $this->openTag('select', array_merge(['name' => $name], $attrs));

        foreach ($options as $val => $label) {
            if (is_array($label)) {
                $html .= $this->openTag('optgroup', ['label' => (string) $val]);
                foreach ($label as $optVal => $optLabel) {
                    $html .= $this->renderOption((string) $optVal, (string) $optLabel, $selectedValue, $isMultiple);
                }
                $html .= '</optgroup>';
            } else {
                $html .= $this->renderOption((string) $val, (string) $label, $selectedValue, $isMultiple);
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
        $hidden = $this->openTag('input', ['type' => 'hidden', 'name' => $name, 'value' => '']);

        return $hidden . $this->checkboxInput($name, $value, $attrs);
    }

    /**
     * Render a checkbox input tag only (no hidden, no unchecked sentinel).
     */
    protected function checkboxInput(string $name, mixed $value, array $attrs): string
    {
        $checked = $this->resolveChecked($name, $value, $attrs);
        unset($attrs['checked']);

        $attrs = array_merge(['type' => 'checkbox', 'name' => $name, 'value' => (string) $value], $attrs);
        if ($checked) {
            $attrs['checked'] = true;
        }

        return $this->openTag('input', $attrs);
    }

    /**
     * Render a radio button.
     */
    public function radio(string $name, mixed $value, array $attrs = []): string
    {
        $checked = $this->resolveChecked($name, $value, $attrs);
        unset($attrs['checked']);

        $attrs = array_merge(['type' => 'radio', 'name' => $name, 'value' => (string) $value], $attrs);
        if ($checked) {
            $attrs['checked'] = true;
        }

        return $this->openTag('input', $attrs);
    }

    /**
     * Render a file input. Never repopulates values.
     */
    public function file(string $name, array $attrs = []): string
    {
        unset($attrs['value']);

        return $this->openTag('input', array_merge([
            'type' => 'file',
            'name' => $name,
        ], $attrs));
    }

    /**
     * Render a hidden input.
     */
    public function hidden(string $name, ?string $value = null, array $attrs = []): string
    {
        unset($attrs['value']);

        $merged = array_merge(['type' => 'hidden', 'name' => $name], $attrs);

        if ($value !== null) {
            $merged['value'] = $value;
        }

        return $this->openTag('input', $merged);
    }

    /**
     * Render a label tag.
     */
    public function label(string $text, string $for = '', array $attrs = []): string
    {
        if ($for !== '') {
            $attrs['for'] = $for;
        }

        return $this->tag('label', $text, $attrs);
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
     * Render a password input. Never repopulates old values.
     */
    public function password(string $name, array $attrs = []): string
    {
        unset($attrs['type'], $attrs['value']);

        return $this->openTag('input', array_merge([
            'type' => 'password',
            'name' => $name,
        ], $attrs));
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
        return $this->openTag('input', array_merge([
            'type' => 'submit',
            'value' => $text,
        ], $attrs));
    }

    /**
     * Render a button element.
     */
    public function button(string $text, array $attrs = []): string
    {
        return $this->tag('button', $text, $attrs);
    }

    /**
     * Render a group of checkboxes (bare tags, no wrapper).
     * Does not emit hidden inputs — use checkbox() for single fields needing unchecked state.
     */
    public function checkboxes(string $name, array $options, array $attrs = []): string
    {
        $html = '';
        foreach ($options as $value => $label) {
            $html .= $this->checkboxInput($name, $value, $attrs) . _e((string) $label);
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
            $html .= $this->radio($name, $value, $attrs) . _e((string) $label);
        }

        return $html;
    }

    /**
     * Render a datalist for autocomplete suggestions.
     */
    public function datalist(string $id, array $options): string
    {
        $html = $this->openTag('datalist', ['id' => $id]);
        foreach ($options as $value) {
            $html .= $this->tag('option', '', ['value' => (string) $value]);
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

        return $message !== '' ? _e($message) : '';
    }

    /**
     * Resolve checked state for checkbox/radio: old() > passed checked attr > false.
     */
    protected function resolveChecked(string $name, mixed $value, array &$attrs): bool
    {
        $oldValue = old($this->nameToDot($name), "\0", false);

        if ($oldValue !== "\0") {
            if (is_array($oldValue)) {
                return in_array((string) $value, $oldValue, true);
            }

            return (string) $oldValue === (string) $value;
        }

        if (isset($attrs['checked'])) {
            return (bool) $attrs['checked'];
        }

        return false;
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
            if (is_array($oldValue)) {
                return $multiple ? $oldValue : null;
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
     * Build an HTML opening tag with attributes (no closing tag).
     */
    protected function openTag(string $name, array $attrs = []): string
    {
        return '<' . $name . $this->buildAttrs($attrs) . '>';
    }

    /**
     * Render a single <option> tag.
     */
    protected function renderOption(string $value, string $label, string|array|null $selected, bool $multiple): string
    {
        $attrs = ['value' => $value];
        if ($this->isSelected($value, $selected, $multiple)) {
            $attrs['selected'] = true;
        }

        return $this->tag('option', $label, $attrs);
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
                $html .= ' ' . _e($key);
                continue;
            }

            $html .= ' ' . _e((string) $key) . '="' . _e((string) $val) . '"';
        }

        return $html;
    }

    /**
     * Build an HTML tag with content and attributes.
     */
    protected function tag(string $name, ?string $content = '', array $attrs = []): string
    {
        return '<' . $name . $this->buildAttrs($attrs) . '>' . _e((string) $content) . '</' . $name . '>';
    }

}
