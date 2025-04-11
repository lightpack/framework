<?php

namespace Lightpack\Utils;

class Script 
{
    /**
     * Convert PHP variable to safe JavaScript code
     */
    public function toJs(mixed $data, bool $asObject = true): string 
    {
        // Convert to JSON with proper escaping
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | 
                                 JSON_HEX_APOS | JSON_HEX_QUOT);

        return $asObject ? $json : "JSON.parse('" . addslashes($json) . "')";
    }

    /**
     * Create a JavaScript variable declaration
     */
    public function var(string $name, mixed $value): string 
    {
        return "var {$name} = " . $this->toJs($value) . ";";
    }

    /**
     * Create a JavaScript constant declaration
     */
    public function const(string $name, mixed $value): string 
    {
        return "const {$name} = " . $this->toJs($value) . ";";
    }

    /**
     * Create a JavaScript let declaration
     */
    public function let(string $name, mixed $value): string 
    {
        return "let {$name} = " . $this->toJs($value) . ";";
    }
}
