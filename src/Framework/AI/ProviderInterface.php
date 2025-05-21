<?php
namespace Lightpack\AI;

/**
 * Interface for all AI providers in Lightpack.
 *
 * @param array $params Supported keys (provider may use subset):
 *   - prompt: string (single prompt for simple completion)
 *   - system: string (system prompt/persona)
 *   - messages: array (conversation history, each with ['role' => ..., 'content' => ...])
 *   - temperature: float
 *   - max_tokens: int
 *   - options: array (custom HTTP/cURL options)
 *   - model: string (model name)
 *   - timeout: int (seconds)
 *   - ... (provider-specific keys)
 *
 * @return array {
 *   @type string $text           The generated text/completion.
 *   @type string $finish_reason  Why the generation stopped (if available).
 *   @type array  $usage          Token usage stats (if available).
 *   @type mixed  $raw            The full raw provider response.
 * }
 */
interface ProviderInterface
{
    /**
     * Generate a completion or response from the provider.
     *
     * @param array $params See interface docblock for supported keys.
     * @return array See interface docblock for return structure.
     */
    public function generate(array $params);

    // Optionally, add more methods for analysis, image, etc.
}
