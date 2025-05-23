# Lightpack AI Integration

Welcome to the Lightpack AI module! This guide will help you understand, configure, and use the AI integration in Lightpack, even if you're completely new to AI or machine learning. The design is simple, explicit, and extensible—true to Lightpack's philosophy.

---

## 🚀 What is this?
This module lets you connect your Lightpack app to AI/ML providers like OpenAI, so you can generate text, ideas, summaries, and more with just a few lines of code.

**What’s under the hood?**
- This integration is built on the OpenAI **Chat Completions API** (using models like `gpt-3.5-turbo`, `gpt-4`, etc.).
- It is perfect for text generation, Q&A, summarization, content suggestions, and any prompt-based text task.
- Other OpenAI APIs (image generation, audio transcription, embeddings, moderation) are **not included by default**, but the architecture is fully pluggable—so you can add these by implementing new providers when needed.

The design is simple, explicit, and extensible—true to Lightpack’s philosophy. Start with OpenAI, and add more providers as you grow.

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

### 🤓 Advanced: Extract JSON from Mixed Output

Sometimes, even with clear prompts, AI models may return extra text before or after the JSON (like explanations or greetings). Here’s how you can robustly extract the JSON part:

```php
$result = $ai->generate([
    'prompt' => "Suggest 5 catchy blog titles for ecommerce. Respond ONLY with a JSON array of strings.",
]);

// Try to extract JSON from mixed output
preg_match('/\[.*\]/s', $result, $matches); // For a JSON array

if (!empty($matches)) {
    $titles = json_decode($matches[0], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($titles)) {
        foreach ($titles as $title) {
            echo "- $title\n";
        }
    } else {
        echo $result;
    }
} else {
    echo $result; // Fallback: print as-is
}
```

**Tips:**
- Adjust the regex if you expect a JSON object: `/\{.*\}/s`
- For complex structures, you can use more sophisticated parsing or libraries.
- Always validate the decoded data before using it in your app.

**Why does this happen?**
Even when instructed, AI models may sometimes add explanations or formatting. This pattern ensures your app remains robust!

---

### 🚀 Further Advanced: Extract Multiple JSON Blobs & Fallback Strategies

Sometimes, an AI response might contain more than one JSON blob (e.g., an object and an array, or multiple arrays), or you may want to ensure you always get *something* usable. Here’s how to handle these scenarios:

#### Extract All JSON Arrays or Objects
```php
$result = $ai->generate([
    'prompt' => "Suggest 5 blog titles and 5 SEO tips for ecommerce. Respond with two JSON arrays: one for titles, one for tips.",
]);

// Extract all JSON arrays from the response
preg_match_all('/\[.*?\]/s', $result, $matches); // Non-greedy match for arrays

if (!empty($matches[0])) {
    $titles = json_decode($matches[0][0], true); // First array
    $tips = isset($matches[0][1]) ? json_decode($matches[0][1], true) : [];
    if (is_array($titles)) {
        echo "Blog Titles:\n";
        foreach ($titles as $title) {
            echo "- $title\n";
        }
    }
    if (is_array($tips)) {
        echo "\nSEO Tips:\n";
        foreach ($tips as $tip) {
            echo "- $tip\n";
        }
    }
} else {
    echo $result; // Fallback
}
```

#### Extract All JSON Objects
```php
preg_match_all('/\{.*?\}/s', $result, $objMatches); // For objects
// Process as needed
```

#### Fallback Strategies
- **Partial Extraction:** If only one array/object is found, use it and log a warning.
- **Graceful Degradation:** If nothing is found, display the raw response or a user-friendly error.

**Tip:**
Always log or alert if extraction fails, so you can improve your prompts or handling logic over time.

---

## 🚦 Intent Detection with Lightpack AI

Intent detection classifies user input (like "Book me a flight to Paris") into predefined categories (intents), just like Dialogflow Essentials. With your Lightpack AI integration, you can build this easily using prompt engineering!

### 1. Define Your Intents

```php
$intents = [
    'BookFlight',
    'CancelBooking',
    'GetWeather',
    'SmallTalk',
];
```

### 2. Basic Intent Detection Prompt

```php
$userInput = "I want to fly to Paris next week.";

$prompt = "Classify the following user message into one of these intents: " . implode(', ', $intents) . ".\nMessage: \"$userInput\"\nIntent:";

$result = $ai->generate([
    'prompt' => $prompt,
    'temperature' => 0, // deterministic output
    'max_tokens' => 10,
]);
$intent = trim($result);
```

### 3. JSON Output with Confidence

```php
$prompt = "Classify the user message into one of these intents: " . implode(', ', $intents) . ".\nMessage: \"$userInput\"\nRespond as JSON: {\"intent\": <intent>, \"confidence\": <0-1>}";

$result = $ai->generate([
    'prompt' => $prompt,
    'temperature' => 0,
    'max_tokens' => 30,
]);
$data = json_decode($result, true);
$intent = $data['intent'] ?? 'Unknown';
$confidence = $data['confidence'] ?? null;
```

### 4. Advanced: Add Training Examples to the Prompt

```php
$prompt = <<<PROMPT
Classify the user message into one of: BookFlight, CancelBooking, GetWeather, SmallTalk.

Examples:
Message: "I want to fly to Paris" → Intent: BookFlight
Message: "Cancel my reservation" → Intent: CancelBooking
Message: "What's the weather in Mumbai?" → Intent: GetWeather
Message: "Hello!" → Intent: SmallTalk

Message: "$userInput"
Intent:
PROMPT;

$result = $ai->generate([
    'prompt' => $prompt,
    'temperature' => 0,
    'max_tokens' => 10,
]);
$intent = trim($result);
```

### 5. How to Organize in Lightpack
- Store intents in config, database, or code.
- Add a helper like `$ai->detectIntent($userInput, $intents)` for reuse.
- Route to the right handler/controller based on the detected intent.

### 📝 Summary
- LLMs + prompt engineering = flexible, accurate intent detection (no need for Dialogflow or proprietary platforms)
- Extend with entity extraction, slot filling, or context as needed

---

## 🧩 Entity Extraction & Slot Filling with Lightpack AI

Beyond intent detection, you can use LLMs for extracting structured data (entities) and filling required slots for tasks like booking, ordering, or Q&A—just like Dialogflow or Rasa!

### 1. What is Entity Extraction?
Entity extraction means pulling out key information from user input (like dates, locations, names).

**Example:**
- User: "Book a flight to Paris on June 5th"
- Entities: {"destination": "Paris", "date": "June 5th"}

### 2. What is Slot Filling?
Slot filling means collecting all required pieces of info for an action. If something is missing, you prompt the user for it.

**Example:**
- Required slots: destination, date
- If date is missing, ask: "When do you want to fly?"

### 3. Entity Extraction with LLMs (Code Example)

```php
$userInput = "Book a flight to Paris on June 5th";
$slots = ['destination', 'date'];

$prompt = "Extract the following slots from the user message: " . implode(', ', $slots) . ".\nMessage: \"$userInput\"\nRespond as JSON: {\"destination\": <destination>, \"date\": <date>}";

$result = $ai->generate([
    'prompt' => $prompt,
    'temperature' => 0,
    'max_tokens' => 50,
]);
$entities = json_decode($result, true);
```

### 4. Slot Filling Example (with Follow-up)

```php
function fillSlots($userInput, $requiredSlots, $currentSlots = []) {
    $missing = array_diff($requiredSlots, array_keys(array_filter($currentSlots)));
    if (empty($missing)) {
        return $currentSlots;
    }
    $prompt = "Extract the following slots from the user message: " . implode(', ', $requiredSlots) . ".\nMessage: \"$userInput\"\nRespond as JSON: {" . implode(': <value>, ', $requiredSlots) . ': <value>}';
    $result = $ai->generate([
        'prompt' => $prompt,
        'temperature' => 0,
        'max_tokens' => 50,
    ]);
    $entities = json_decode($result, true);
    $filled = array_merge($currentSlots, array_filter($entities));
    $missing = array_diff($requiredSlots, array_keys(array_filter($filled)));
    if (!empty($missing)) {
        // Ask user for the next missing slot
        $next = reset($missing);
        echo "Please provide your $next.";
    }
    return $filled;
}
```

### 5. Prompt Engineering Tips
- Always ask for JSON output for easy parsing.
- List required slots explicitly in the prompt.
- Provide examples for clarity if needed.
- Use temperature 0 for deterministic extraction.

### 6. Example Prompt for Extraction
```
Extract the following slots from the user message: destination, date.
Message: "Book a flight to Paris on June 5th"
Respond as JSON: {"destination": <destination>, "date": <date>}
```

**Sample AI Response:**
```json
{"destination": "Paris", "date": "June 5th"}
```

---

## 🛠️ Advanced: SlotFiller Helper Class for Multi-Turn Conversations

For more advanced conversational AI, you can encapsulate slot filling logic in a reusable helper class. This makes it easy to manage state, validation, and user prompts across multiple turns—Lightpack style!

### SlotFiller Example (PHP Pseudocode)

```php
namespace Lightpack\AI;

class SlotFiller
{
    protected $ai;
    protected $requiredSlots;
    protected $slots = [];

    public function __construct($ai, array $requiredSlots)
    {
        $this->ai = $ai;
        $this->requiredSlots = $requiredSlots;
    }

    public function fill($userInput)
    {
        $prompt = "Extract the following slots from the user message: " . implode(', ', $this->requiredSlots) . ".\nMessage: \"$userInput\"\nRespond as JSON: {" . implode(': <value>, ', $this->requiredSlots) . ': <value>}';
        $result = $this->ai->generate([
            'prompt' => $prompt,
            'temperature' => 0,
            'max_tokens' => 50,
        ]);
        $entities = json_decode($result, true);
        if (is_array($entities)) {
            $this->slots = array_merge($this->slots, array_filter($entities));
        }
        return $this->slots;
    }

    public function missingSlots()
    {
        return array_diff($this->requiredSlots, array_keys(array_filter($this->slots)));
    }

    public function isComplete()
    {
        return empty($this->missingSlots());
    }

    public function reset()
    {
        $this->slots = [];
    }
}
```

### Usage Example

```php
$requiredSlots = ['destination', 'date'];
$slotFiller = new \Lightpack\AI\SlotFiller($ai, $requiredSlots);

while (!$slotFiller->isComplete()) {
    $userInput = getUserInput(); // however you get user input
    $slots = $slotFiller->fill($userInput);
    $missing = $slotFiller->missingSlots();
    if (!empty($missing)) {
        $next = reset($missing);
        echo "Please provide your $next.\n";
    }
}
echo "Booking a flight to {$slots['destination']} on {$slots['date']}!";
```

### Add Validation
You can extend the class with validation methods for each slot (e.g., date format, allowed destinations) before accepting the value.

### Store State
For real apps, store `$slotFiller->slots` in the session or conversation context between requests.

---

## ❤️ Lightpack Philosophy
- Simple, explicit, and practical
- No magic, no hidden state
- Extensible by design
- Clear separation of concerns

If you have questions, ideas, or want to contribute, open an issue or PR!
