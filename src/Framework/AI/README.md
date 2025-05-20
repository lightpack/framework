# Lightpack AI Integration

Welcome to the Lightpack AI module! This guide will help you understand, configure, and use the AI integration in Lightpack, even if you're completely new to AI or machine learning. The design is simple, explicit, and extensible—true to Lightpack's philosophy.

---

## 🚀 What is this?
This module lets you connect your Lightpack app to AI/ML providers like OpenAI, so you can generate text, ideas, summaries, and more with just a few lines of code. The architecture is pluggable: start with OpenAI, add more providers as you grow.

---

## 📦 Folder Structure

```
AI/
  ProviderInterface.php   # Contract for all AI providers
  OpenAIProvider.php      # Implementation for OpenAI
  AI.php                  # Service for unified usage
  README.md               # This documentation
```

---

## 🛠️ Configuration (`config/ai.php`)

All your AI settings live in `config/ai.php`. Example:

```php
return [
    'default' => 'openai',
    'providers' => [
        'openai' => [
            'key' => get_env('OPENAI_KEY'),
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 256,
            'timeout' => 15,
        ],
        // Add more providers here
    ],
];
```

- **key**: Your API key for the provider (keep this secret!)
- **model**: The AI model to use (e.g., `gpt-3.5-turbo`, `gpt-4`)
- **temperature**: Controls creativity. Lower = more predictable, higher = more creative (range: 0.0–2.0)
- **max_tokens**: Max length of AI output (1 token ≈ 3/4 word)
- **timeout**: How long to wait for a response (in seconds)

You can override any of these per request.

---

## 🧑‍💻 Usage Example

```php
use Lightpack\AI\AI;

$ai = new AI($config, $http, $logger, $cache);

// Generate 5 blog titles for ecommerce
$result = $ai->generate([
    'prompt' => 'Suggest 5 catchy blog titles for ecommerce.',
    'temperature' => 0.6, // optional, overrides config
]);

echo $result;
```

---

## 🔍 How Does It Work?
- You call `$ai->generate([...])` with your prompt and options.
- The AI module loads the configured provider (OpenAI by default).
- It sends your request to the AI provider and returns the result.
- Results can be cached for efficiency.

---

## 🧩 Extending: Adding More Providers
- Implement `ProviderInterface` for your new provider.
- Register it in the config.
- The rest of your app code stays the same!

---

## 🧠 About the `generate(array $params)` Method

### What it CAN do:
- Generate any kind of text/content: blog titles, summaries, code, strategies, etc.
- Accept flexible parameters: prompt, model, temperature, max_tokens, etc.
- Work with any provider that supports text/content generation.
- Return results as plain text (or structured, if your prompt asks for it).
- Be extended for advanced use cases (e.g., batch, structured output).

### What it CANNOT do (by default):
- Specialized tasks like image generation, audio transcription, or translation (unless you encode those as prompts).
- Batch/streaming results (unless you build it into your provider).
- Enforce parameter validation or output schema (you must handle this in your app).
- Asynchronous/queued jobs (use Lightpack Jobs for that).
- Multi-modal or multi-turn (chat) flows (unless you pass all context in params).

**Summary:** It's a flexible, minimal, provider-agnostic entry point for AI/ML generation tasks—perfect for most text/content use cases.

---

## ⚙️ Key AI Concepts Explained

- **Prompt**: The input text or question you give the AI (e.g., "Suggest 5 blog titles for ecommerce.")
- **Model**: The specific AI engine (e.g., `gpt-3.5-turbo`, `gpt-4`). Newer models are usually smarter.
- **Temperature**: Controls randomness/creativity. 0 = always the same answer, 1+ = more varied/creative.
- **Max Tokens**: The maximum length of the AI's response. 1 token ≈ 3/4 word. Limits cost and verbosity.
- **API Key**: Secret credential for accessing the provider. Never commit this to version control!
- **Timeout**: How long to wait for the provider to reply before failing.
- **Caching**: Storing previous results to save time/cost for repeated queries.

---

## 🧪 Testing & Debugging
- Use Lightpack's testing tools to mock AI responses.
- Log all requests/responses for transparency and debugging.
- Handle errors gracefully—AI providers can rate limit or fail.

---

## 🤔 FAQ

**Q: Can I generate images/audio/code?**  
A: This module is focused on text/content generation, but you can extend it for more tasks by adding new providers or methods.

**Q: Is it safe to use in production?**  
A: Yes, but make sure to secure your API keys and handle errors/costs.

**Q: How do I add a new provider?**  
A: Implement `ProviderInterface`, add it to the config, and you're done.

**Q: Can I use this for batch jobs or async tasks?**  
A: Use Lightpack's Jobs system to queue up AI tasks for background processing.

---

## 🧑‍🍳 Practical Code Recipes

Here are real-world, ready-to-use code samples for common AI tasks. Copy, tweak, and build your own magic!

### 1. Generate Blog Titles
```php
use Lightpack\AI\AI;
$ai = new AI($config, $http, $logger, $cache);

$result = $ai->generate([
    'prompt' => 'Suggest 5 catchy blog titles for ecommerce.',
]);
echo $result;
```

### 2. Summarize an Article
```php
$article = "Paste your article here...";
$result = $ai->generate([
    'prompt' => "Summarize this article in 3 bullet points: $article",
    'max_tokens' => 150,
]);
echo $result;
```

### 3. SEO Score Suggestion
```php
$content = "Paste your blog content here...";
$result = $ai->generate([
    'prompt' => "Give an SEO score (1-10) and 3 improvement tips for this content: $content",
    'temperature' => 0.3,
]);
echo $result;
```

### 4. Rewriting Content
```php
$text = "Your original paragraph here.";
$result = $ai->generate([
    'prompt' => "Rewrite this to be more engaging: $text",
    'temperature' => 0.9, // More creative
]);
echo $result;
```

### 5. Generate Code Snippets
```php
$task = "Write a PHP function to reverse a string.";
$result = $ai->generate([
    'prompt' => $task,
    'model' => 'gpt-4', // Use advanced model if available
]);
echo $result;
```

### 6. Creative Writing (Poetry)
```php
$result = $ai->generate([
    'prompt' => "Write a short poem about teamwork in software development.",
    'temperature' => 1.2, // High creativity
    'max_tokens' => 100,
]);
echo $result;
```

### 7. Controlling Output Length
```php
$result = $ai->generate([
    'prompt' => "Explain quantum computing to a 10-year-old.",
    'max_tokens' => 60, // Short answer
]);
echo $result;
```

### 8. Handling Errors Gracefully
```php
try {
    $result = $ai->generate([
        'prompt' => 'Generate a haiku about rainy days.',
    ]);
    echo $result;
} catch (Exception $e) {
    // Log or show a user-friendly message
    echo "Sorry, the AI service is currently unavailable.";
}
```

### 9. Using Caching for Repeated Prompts
```php
// If your provider and config support caching, repeated prompts are auto-cached.
$result1 = $ai->generate([
    'prompt' => 'List 10 creative startup ideas.',
]);
// The second call with the same prompt returns instantly from cache!
$result2 = $ai->generate([
    'prompt' => 'List 10 creative startup ideas.',
]);
```

### 10. Switching Providers (when you add more)
```php
// Example for future: If you add another provider, just change the config
$config['default'] = 'myotherai';
$ai = new AI($config, $http, $logger, $cache);
```

---

### 🏆 Pro Tip: Get Structured (JSON) Responses

You can ask the AI to return structured data, like a JSON array or object. This is super useful for lists, FAQs, or anything you want to process in code!

**Example: Get 5 Blog Titles as JSON**

```php
$result = $ai->generate([
    'prompt' => "Suggest 5 catchy blog titles for ecommerce. Respond ONLY with a JSON array of strings.",
    // You can add 'temperature', 'max_tokens', etc. as needed
]);

$titles = json_decode($result, true);

if (json_last_error() === JSON_ERROR_NONE && is_array($titles)) {
    foreach ($titles as $title) {
        echo "- $title\n";
    }
} else {
    // Fallback: print raw result if not valid JSON
    echo $result;
}
```

**Prompting Tips:**
- Be explicit: “Respond ONLY with a JSON array of 5 blog titles, no explanation.”
- For objects: “Return a JSON object with keys as numbers and values as blog titles.”
- For complex data, describe the exact structure you want.

**Always validate the response** before using it in your app, as AI models may sometimes output extra text. If needed, use regex or string manipulation to extract the JSON.

**Sample Prompt:**
```
Suggest 5 catchy blog titles for ecommerce. Respond ONLY with a JSON array of strings.
```

**Sample AI Response:**
```json
[
  "10 Secrets to Skyrocket Your Ecommerce Sales",
  "The Ultimate Guide to Online Store Success",
  "Boost Your Brand with These Ecommerce Strategies",
  "How to Turn Browsers into Buyers",
  "Top Trends Shaping Ecommerce in 2025"
]
```

---

## ❤️ Lightpack Philosophy
- Simple, explicit, and practical
- No magic, no hidden state
- Extensible by design
- Clear separation of concerns

If you have questions, ideas, or want to contribute, open an issue or PR!
