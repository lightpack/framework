<?php

/**
 * AI Streaming Examples
 * 
 * This file demonstrates how to use the AI streaming feature
 * for real-time, interactive AI responses.
 */

namespace Examples;

class AIStreamingExample
{
    /**
     * Example 1: Simple streaming question
     * 
     * Usage in a route:
     * app('route')->get('/ai/stream/simple', [AIStreamingExample::class, 'simpleStream']);
     */
    public function simpleStream()
    {
        $ai = app('openai'); // or app('anthropic'), app('groq'), app('mistral')
        
        // This returns a Response object configured for SSE streaming
        return $ai->askStream('Explain quantum computing in simple terms');
    }

    /**
     * Example 2: Streaming with custom options
     */
    public function streamWithOptions()
    {
        $ai = app('openai');
        
        return $ai->askStream('Write a short poem about coding', [
            'model' => 'gpt-4',
            'temperature' => 0.9,
            'max_tokens' => 200
        ]);
    }

    /**
     * Example 3: Using TaskBuilder for streaming
     */
    public function taskBuilderStream()
    {
        $ai = app('anthropic');
        
        return $ai->task()
            ->system('You are a helpful programming tutor')
            ->prompt('Explain recursion with a simple example')
            ->model('claude-3-sonnet')
            ->temperature(0.7)
            ->stream();
    }

    /**
     * Example 4: Streaming with conversation history
     */
    public function conversationStream()
    {
        $ai = app('openai');
        
        return $ai->task()
            ->system('You are a friendly AI assistant')
            ->message('user', 'What is machine learning?')
            ->message('assistant', 'Machine learning is a subset of AI that enables systems to learn from data.')
            ->message('user', 'Can you give me a practical example?')
            ->stream();
    }

    /**
     * Example 5: Dynamic streaming based on user input
     * 
     * This is what you'd typically use in a real application
     */
    public function dynamicStream()
    {
        // Get user question from request
        $question = request()->input('question');
        
        // Validate input
        if (empty($question)) {
            return response()->json(['error' => 'Question is required'], 400);
        }
        
        if (strlen($question) > 1000) {
            return response()->json(['error' => 'Question too long'], 400);
        }
        
        // Sanitize
        $question = strip_tags($question);
        
        // Get AI provider from request or use default
        $provider = request()->input('provider', 'openai');
        $ai = app($provider);
        
        // Stream the response
        return $ai->askStream($question);
    }

    /**
     * Example 6: Streaming with error handling
     */
    public function streamWithErrorHandling()
    {
        try {
            $ai = app('openai');
            $question = request()->input('question', 'What is AI?');
            
            return $ai->askStream($question);
            
        } catch (\Exception $e) {
            // Log the error
            app('logger')->error('AI streaming error: ' . $e->getMessage());
            
            // Return error response
            return response()->json([
                'error' => 'Failed to stream AI response',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example 7: Streaming with multiple providers
     */
    public function multiProviderStream()
    {
        $provider = request()->input('provider', 'openai');
        $question = request()->input('question', 'Explain AI');
        
        // Map of available providers
        $providers = [
            'openai' => app('openai'),
            'anthropic' => app('anthropic'),
            'groq' => app('groq'),
            'mistral' => app('mistral'),
        ];
        
        if (!isset($providers[$provider])) {
            return response()->json(['error' => 'Invalid provider'], 400);
        }
        
        $ai = $providers[$provider];
        
        return $ai->askStream($question);
    }

    /**
     * Example 8: Streaming with system prompt customization
     */
    public function customSystemPromptStream()
    {
        $role = request()->input('role', 'assistant');
        $question = request()->input('question');
        
        // Different system prompts for different roles
        $systemPrompts = [
            'assistant' => 'You are a helpful AI assistant.',
            'tutor' => 'You are an expert programming tutor. Explain concepts clearly with examples.',
            'poet' => 'You are a creative poet. Respond in verse.',
            'scientist' => 'You are a scientist. Provide accurate, evidence-based answers.',
        ];
        
        $systemPrompt = $systemPrompts[$role] ?? $systemPrompts['assistant'];
        
        $ai = app('openai');
        
        return $ai->task()
            ->system($systemPrompt)
            ->prompt($question)
            ->stream();
    }

    /**
     * Example 9: Streaming with conversation context
     */
    public function contextualStream()
    {
        $question = request()->input('question');
        $history = request()->input('history', []); // Array of previous messages
        
        $ai = app('openai');
        $task = $ai->task()->system('You are a helpful assistant');
        
        // Add conversation history
        foreach ($history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $task->message($msg['role'], $msg['content']);
            }
        }
        
        // Add current question
        $task->message('user', $question);
        
        return $task->stream();
    }

    /**
     * Example 10: Streaming with rate limiting
     * 
     * Note: This requires the RateLimiter filter to be set up in routes
     */
    public function rateLimitedStream()
    {
        // Check if user has exceeded rate limit
        $userId = auth()->id();
        $cacheKey = "ai_stream_rate_limit:$userId";
        $cache = app('cache');
        
        $requestCount = $cache->get($cacheKey, 0);
        
        if ($requestCount >= 10) {
            return response()->json([
                'error' => 'Rate limit exceeded. Please try again later.'
            ], 429);
        }
        
        // Increment counter
        $cache->set($cacheKey, $requestCount + 1, 60); // 1 minute TTL
        
        // Process request
        $ai = app('openai');
        $question = request()->input('question');
        
        return $ai->askStream($question);
    }
}

/**
 * Example Routes Configuration
 * 
 * Add these to your routes file:
 */

/*
use Examples\AIStreamingExample;

// Simple streaming
app('route')->get('/ai/stream/simple', [AIStreamingExample::class, 'simpleStream']);

// Streaming with options
app('route')->get('/ai/stream/options', [AIStreamingExample::class, 'streamWithOptions']);

// TaskBuilder streaming
app('route')->get('/ai/stream/task', [AIStreamingExample::class, 'taskBuilderStream']);

// Dynamic streaming (most common use case)
app('route')->post('/ai/stream', [AIStreamingExample::class, 'dynamicStream']);

// Multi-provider streaming
app('route')->post('/ai/stream/multi', [AIStreamingExample::class, 'multiProviderStream']);

// Custom system prompt
app('route')->post('/ai/stream/custom', [AIStreamingExample::class, 'customSystemPromptStream']);

// Contextual conversation
app('route')->post('/ai/stream/context', [AIStreamingExample::class, 'contextualStream']);
*/

/**
 * Frontend JavaScript Example
 * 
 * Use this in your HTML/JavaScript to consume the streaming endpoint:
 */

/*
<script>
async function streamAI(question) {
    const responseDiv = document.getElementById('ai-response');
    responseDiv.textContent = '';
    
    const response = await fetch('/ai/stream', {
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
        
        if (done) {
            console.log('Stream completed');
            break;
        }
        
        const chunk = decoder.decode(value);
        const lines = chunk.split('\n');
        
        for (const line of lines) {
            if (line.startsWith('data: ')) {
                const data = line.substring(6);
                
                if (data === '[DONE]') {
                    return;
                }
                
                try {
                    const parsed = JSON.parse(data);
                    
                    if (parsed.error) {
                        console.error('Error:', parsed.error);
                        responseDiv.textContent += '\n[Error: ' + parsed.error + ']';
                        return;
                    }
                    
                    if (parsed.content) {
                        responseDiv.textContent += parsed.content;
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                }
            }
        }
    }
}

// Usage
document.getElementById('ask-button').addEventListener('click', () => {
    const question = document.getElementById('question-input').value;
    streamAI(question);
});
</script>
*/
