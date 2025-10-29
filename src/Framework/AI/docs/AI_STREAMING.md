# AI Streaming Support

## Overview

The Lightpack AI module now supports **real-time streaming responses** using Server-Sent Events (SSE), similar to ChatGPT, Claude, Gemini, and Windsurf. This provides a more interactive user experience by displaying AI responses as they are generated, token by token.

## Features

- ✅ **Real-time streaming** - Tokens appear as they're generated
- ✅ **Server-Sent Events (SSE)** - Standard web protocol for streaming
- ✅ **All providers supported** - OpenAI, Anthropic, Groq, Mistral
- ✅ **Simple API** - Easy to use with `askStream()` and `stream()` methods
- ✅ **Error handling** - Graceful error recovery
- ✅ **Unicode support** - Handles emojis and international characters
- ✅ **Performance optimized** - Efficient buffering and flushing

## Quick Start

### Basic Streaming

```php
// Simple streaming question
$ai = app('ai'); // or app('openai'), app('anthropic'), etc.

return $ai->askStream('Explain quantum computing in simple terms');
```

This returns a `Response` object configured for SSE streaming. When sent to the browser, it will stream the AI response in real-time.

### Using TaskBuilder

```php
$response = $ai->task()
    ->prompt('Write a short story about a robot')
    ->model('gpt-4')
    ->temperature(0.8)
    ->stream();

return $response;
```

### In a Controller

```php
use Lightpack\Http\Response;

class ChatController
{
    public function stream()
    {
        $question = request()->input('question');
        
        $ai = app('openai');
        
        // Returns a streaming response
        return $ai->askStream($question);
    }
}
```

## Frontend Integration

### JavaScript EventSource API

```javascript
const eventSource = new EventSource('/api/chat/stream?question=What+is+AI');

eventSource.onmessage = function(event) {
    if (event.data === '[DONE]') {
        eventSource.close();
        console.log('Stream completed');
        return;
    }
    
    const data = JSON.parse(event.data);
    
    if (data.error) {
        console.error('Error:', data.error);
        return;
    }
    
    if (data.content) {
        // Append content to your UI
        document.getElementById('response').textContent += data.content;
    }
};

eventSource.onerror = function(error) {
    console.error('EventSource error:', error);
    eventSource.close();
};
```

### Fetch API with Streaming

```javascript
async function streamAI(question) {
    const response = await fetch('/api/chat/stream', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ question })
    });
    
    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    
    while (true) {
        const { done, value } = await reader.read();
        
        if (done) break;
        
        const chunk = decoder.decode(value);
        const lines = chunk.split('\n');
        
        for (const line of lines) {
            if (line.startsWith('data: ')) {
                const data = line.substring(6);
                
                if (data === '[DONE]') {
                    console.log('Stream completed');
                    return;
                }
                
                try {
                    const parsed = JSON.parse(data);
                    if (parsed.content) {
                        // Update UI with new content
                        document.getElementById('response').textContent += parsed.content;
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                }
            }
        }
    }
}
```

## Advanced Usage

### Custom Options

```php
$response = $ai->askStream('Explain machine learning', [
    'model' => 'gpt-4-turbo',
    'temperature' => 0.7,
    'max_tokens' => 500
]);

return $response;
```

### With System Prompt

```php
$response = $ai->task()
    ->system('You are a helpful coding assistant')
    ->prompt('Write a Python function to sort a list')
    ->stream();

return $response;
```

### Conversation History

```php
$response = $ai->task()
    ->message('user', 'What is recursion?')
    ->message('assistant', 'Recursion is when a function calls itself.')
    ->message('user', 'Can you give me an example?')
    ->stream();

return $response;
```

## Response Format

### SSE Data Format

Each streamed chunk follows this format:

```
data: {"content": "Hello"}

data: {"content": " world"}

data: [DONE]
```

### Error Format

```
data: {"error": "Connection timeout"}
```

## Provider-Specific Notes

### OpenAI & Groq
- Use `stream: true` parameter
- Delta format: `choices[0].delta.content`
- Completion signal: `data: [DONE]`

### Anthropic (Claude)
- Use `stream: true` parameter
- Event types: `content_block_delta`, `message_stop`
- Delta format: `delta.text`

### Mistral
- OpenAI-compatible streaming format
- Same delta structure as OpenAI

## Performance Considerations

### Buffer Management
The implementation automatically:
- Disables output buffering with `ob_end_clean()`
- Flushes content immediately with `flush()`
- Handles incomplete lines in the buffer

### Connection Handling
- Checks connection status to detect client disconnects
- Gracefully terminates streaming on disconnect
- Prevents memory leaks with proper cleanup

### Timeout Configuration

```php
// In your config/ai.php
return [
    'http_timeout' => 30, // Streaming timeout in seconds
    // ... other config
];
```

## Testing

### Unit Tests

```php
use PHPUnit\Framework\TestCase;

class MyStreamingTest extends TestCase
{
    public function testStreaming()
    {
        $ai = app('openai');
        $response = $ai->askStream('Test question');
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('text/event-stream', $response->getType());
        $this->assertNotNull($response->getStreamCallback());
    }
}
```

### Integration Tests

Run the included tests:

```bash
./vendor/bin/phpunit tests/AI/StreamingTest.php
./vendor/bin/phpunit tests/AI/StreamingIntegrationTest.php
```

## Security Considerations

### Headers
The implementation sets secure headers:
- `Cache-Control: no-cache` - Prevents caching of sensitive data
- `Connection: keep-alive` - Maintains connection for streaming
- `X-Accel-Buffering: no` - Disables nginx buffering

### Input Validation
Always validate and sanitize user input:

```php
$question = request()->input('question');

// Validate
if (empty($question) || strlen($question) > 1000) {
    return response()->json(['error' => 'Invalid question'], 400);
}

// Sanitize
$question = strip_tags($question);

return $ai->askStream($question);
```

### Rate Limiting
Consider implementing rate limiting for streaming endpoints:

```php
use Lightpack\Filters\RateLimiter;

// In your routes
app('route')->post('/api/chat/stream', [ChatController::class, 'stream'])
    ->filter(new RateLimiter(10, 60)); // 10 requests per minute
```

## Troubleshooting

### No Output Appears

**Problem**: Browser doesn't show streaming content

**Solution**: Check if output buffering is enabled in php.ini:
```ini
output_buffering = Off
```

Or disable it in your code:
```php
if (ob_get_level()) {
    ob_end_clean();
}
```

### Connection Timeouts

**Problem**: Stream disconnects prematurely

**Solution**: Increase timeout in config:
```php
'http_timeout' => 60, // Increase to 60 seconds
```

### CORS Issues

**Problem**: Browser blocks EventSource requests

**Solution**: Add CORS headers:
```php
$response->setHeader('Access-Control-Allow-Origin', '*');
$response->setHeader('Access-Control-Allow-Methods', 'GET, POST');
```

### Nginx Buffering

**Problem**: Nginx buffers the response

**Solution**: Add to nginx config:
```nginx
location /api/chat/stream {
    proxy_buffering off;
    proxy_cache off;
}
```

## Examples

### Complete Chat Interface

```php
// Controller
class ChatController
{
    public function stream()
    {
        $question = request()->input('question');
        $history = request()->input('history', []);
        
        $ai = app('openai');
        $task = $ai->task()->system('You are a helpful assistant');
        
        // Add conversation history
        foreach ($history as $msg) {
            $task->message($msg['role'], $msg['content']);
        }
        
        // Add current question
        $task->message('user', $question);
        
        return $task->stream();
    }
}

// Route
app('route')->post('/api/chat/stream', [ChatController::class, 'stream']);
```

```html
<!-- Frontend -->
<!DOCTYPE html>
<html>
<head>
    <title>AI Chat</title>
    <style>
        #response { 
            white-space: pre-wrap; 
            font-family: monospace;
            padding: 20px;
            border: 1px solid #ccc;
            min-height: 200px;
        }
        .typing::after {
            content: '▋';
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
    </style>
</head>
<body>
    <input type="text" id="question" placeholder="Ask a question...">
    <button onclick="askQuestion()">Ask</button>
    <div id="response" class="typing"></div>
    
    <script>
        async function askQuestion() {
            const question = document.getElementById('question').value;
            const responseDiv = document.getElementById('response');
            responseDiv.textContent = '';
            responseDiv.classList.add('typing');
            
            const response = await fetch('/api/chat/stream', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question })
            });
            
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                
                const chunk = decoder.decode(value);
                const lines = chunk.split('\n');
                
                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        const data = line.substring(6);
                        if (data === '[DONE]') {
                            responseDiv.classList.remove('typing');
                            return;
                        }
                        
                        try {
                            const parsed = JSON.parse(data);
                            if (parsed.content) {
                                responseDiv.textContent += parsed.content;
                            }
                        } catch (e) {}
                    }
                }
            }
        }
    </script>
</body>
</html>
```

## Comparison: Regular vs Streaming

### Regular (Non-Streaming)

```php
// User waits for entire response
$result = $ai->ask('Write a long essay');
return response()->json(['text' => $result]);
```

**User Experience**: 
- ⏳ Wait 10-30 seconds
- 📄 See complete response at once

### Streaming

```php
// User sees response as it's generated
return $ai->askStream('Write a long essay');
```

**User Experience**:
- ⚡ See first words in 1-2 seconds
- 📝 Watch response being typed in real-time
- 🎯 More engaging and interactive

## Conclusion

Streaming AI responses significantly improves user experience by providing immediate feedback and a more interactive feel. The implementation is production-ready, fully tested, and supports all major AI providers.

For questions or issues, refer to the test files:
- `tests/AI/StreamingTest.php` - Unit tests
- `tests/AI/StreamingIntegrationTest.php` - Integration tests
