<?php

declare(strict_types=1);

namespace Lightpack\Validation\Traits;

trait ValidationMessageTrait
{
    /**
     * The validation error message (English fallback)
     */
    protected string $message = 'Validation failed';

    /**
     * The lang file key, e.g. 'validation.required'.
     * Empty string means lang resolution is skipped.
     */
    protected string $langKey = '';

    /**
     * Placeholder values for the lang string, e.g. ['min' => 5].
     */
    protected array $messageParams = [];

    /**
     * Get the validation error message.
     *
     * Resolution order:
     *   1. lang($langKey, $messageParams) — when a key is set and the
     *      translation file exists for the current locale.
     *   2. Raw $message — English fallback (always available, no file needed).
     */
    public function getMessage(): string
    {
        if ($this->langKey !== '' && function_exists('lang')) {
            try {
                $translated = lang($this->langKey, $this->messageParams);
                if ($translated !== $this->langKey) {
                    return $translated;
                }
            } catch (\Throwable $e) {
                // Container not bootstrapped (e.g. pure unit-test context).
            }
        }

        return $this->message;
    }

    /**
     * Set a custom validation error message.
     * Clears $langKey so this explicit value is always used.
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
        $this->langKey = '';
    }
}
