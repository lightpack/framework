<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\TaskBuilder;

class MultimodalProvider
{
    public array $lastParams = [];
    
    public function generate($params)
    {
        $this->lastParams = $params;
        return ['text' => 'Multimodal response'];
    }
}

class MultimodalContentTest extends TestCase
{
    public function testTextOnlyContentPassesThrough()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        $result = $builder
            ->message('user', 'Simple text message')
            ->run();
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($provider->lastParams['messages']);
        $this->assertEquals('Simple text message', $provider->lastParams['messages'][0]['content']);
    }
    
    public function testLegacyArrayContentGetsJoined()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        $result = $builder
            ->message('user', ['Line 1', 'Line 2', 'Line 3'])
            ->run();
        
        $this->assertTrue($result['success']);
        // TaskBuilder stores arrays as-is, normalization happens in provider
        $this->assertIsArray($provider->lastParams['messages']);
        // Content is still array in params (not yet normalized by provider's prepareRequestBody)
        $this->assertIsArray($provider->lastParams['messages'][0]['content']);
    }
    
    public function testMultimodalImageContentPassesThrough()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        $multimodalContent = [
            ['type' => 'text', 'text' => 'What is in this image?'],
            ['type' => 'image_url', 'image_url' => [
                'url' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg...',
                'detail' => 'high'
            ]]
        ];
        
        $result = $builder
            ->message('user', $multimodalContent)
            ->run();
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($provider->lastParams['messages']);
        $this->assertIsArray($provider->lastParams['messages'][0]['content']);
        $this->assertCount(2, $provider->lastParams['messages'][0]['content']);
        $this->assertEquals('text', $provider->lastParams['messages'][0]['content'][0]['type']);
        $this->assertEquals('image_url', $provider->lastParams['messages'][0]['content'][1]['type']);
    }
    
    public function testMultimodalDocumentContentPassesThrough()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        $multimodalContent = [
            ['type' => 'text', 'text' => 'Summarize this document'],
            ['type' => 'document', 'source' => [
                'type' => 'base64',
                'media_type' => 'application/pdf',
                'data' => 'JVBERi0xLjQKJeLjz9MKMSAwIG9iago8PC...'
            ]]
        ];
        
        $result = $builder
            ->message('user', $multimodalContent)
            ->run();
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($provider->lastParams['messages']);
        $this->assertIsArray($provider->lastParams['messages'][0]['content']);
        $this->assertCount(2, $provider->lastParams['messages'][0]['content']);
        $this->assertEquals('text', $provider->lastParams['messages'][0]['content'][0]['type']);
        $this->assertEquals('document', $provider->lastParams['messages'][0]['content'][1]['type']);
    }
    
    public function testMultipleImagesInSingleMessage()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        $multimodalContent = [
            ['type' => 'text', 'text' => 'Compare these two images'],
            ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,IMAGE1']],
            ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,IMAGE2']]
        ];
        
        $result = $builder
            ->message('user', $multimodalContent)
            ->run();
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($provider->lastParams['messages'][0]['content']);
        $this->assertCount(3, $provider->lastParams['messages'][0]['content']);
    }
    
    public function testMixedMessagesInConversation()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        $result = $builder
            ->message('user', 'Hello')
            ->message('assistant', 'Hi there!')
            ->message('user', [
                ['type' => 'text', 'text' => 'Look at this'],
                ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,IMG']]
            ])
            ->run();
        
        $this->assertTrue($result['success']);
        $this->assertCount(3, $provider->lastParams['messages']);
        
        // First message: plain text
        $this->assertEquals('Hello', $provider->lastParams['messages'][0]['content']);
        
        // Second message: plain text
        $this->assertEquals('Hi there!', $provider->lastParams['messages'][1]['content']);
        
        // Third message: multimodal array
        $this->assertIsArray($provider->lastParams['messages'][2]['content']);
        $this->assertCount(2, $provider->lastParams['messages'][2]['content']);
    }
    
    public function testEmptyArrayContentIsNotMultimodal()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        $result = $builder
            ->message('user', [])
            ->run();
        
        $this->assertTrue($result['success']);
        // Empty array stored as-is, normalization happens in provider
        $this->assertIsArray($provider->lastParams['messages'][0]['content']);
        $this->assertEmpty($provider->lastParams['messages'][0]['content']);
    }
    
    public function testSystemPromptWithMultimodalUserMessage()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        $result = $builder
            ->system('You are a helpful image analyzer')
            ->message('user', [
                ['type' => 'text', 'text' => 'Analyze this'],
                ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,IMG']]
            ])
            ->run();
        
        $this->assertTrue($result['success']);
        // System prompt gets prepended as a message
        $this->assertCount(2, $provider->lastParams['messages']);
        $this->assertEquals('system', $provider->lastParams['messages'][0]['role']);
        $this->assertEquals('user', $provider->lastParams['messages'][1]['role']);
        $this->assertIsArray($provider->lastParams['messages'][1]['content']);
    }
    
    public function testMultimodalContentWithPromptMethod()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        // When using prompt(), it stores as prompt parameter
        $result = $builder
            ->prompt('Simple prompt')
            ->run();
        
        $this->assertTrue($result['success']);
        // prompt() sets 'prompt' key, provider converts to messages internally
        $this->assertArrayHasKey('prompt', $provider->lastParams);
        $this->assertEquals('Simple prompt', $provider->lastParams['prompt']);
    }
    
    public function testAnthropicStyleImageContent()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        // Anthropic uses 'image' type with 'source' structure
        $multimodalContent = [
            ['type' => 'text', 'text' => 'Describe this'],
            ['type' => 'image', 'source' => [
                'type' => 'base64',
                'media_type' => 'image/jpeg',
                'data' => 'BASE64DATA'
            ]]
        ];
        
        $result = $builder
            ->message('user', $multimodalContent)
            ->run();
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($provider->lastParams['messages'][0]['content']);
        $this->assertEquals('image', $provider->lastParams['messages'][0]['content'][1]['type']);
    }
    
    public function testGeminiStyleInlineData()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        // Gemini uses 'inline_data' structure
        $multimodalContent = [
            ['type' => 'text', 'text' => 'What is this?'],
            ['inline_data' => [
                'mime_type' => 'image/jpeg',
                'data' => 'BASE64DATA'
            ]]
        ];
        
        $result = $builder
            ->message('user', $multimodalContent)
            ->run();
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($provider->lastParams['messages'][0]['content']);
        $this->assertArrayHasKey('inline_data', $provider->lastParams['messages'][0]['content'][1]);
    }
    
    public function testMultimodalDetectionWithNestedArrays()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        // This should be detected as multimodal because first element has 'type' key
        $multimodalContent = [
            ['type' => 'text', 'text' => 'Question'],
            ['type' => 'image_url', 'image_url' => [
                'url' => 'http://example.com/image.jpg',
                'detail' => 'auto'
            ]]
        ];
        
        $result = $builder
            ->message('user', $multimodalContent)
            ->run();
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($provider->lastParams['messages'][0]['content']);
        $this->assertIsArray($provider->lastParams['messages'][0]['content'][1]['image_url']);
    }
    
    public function testNonMultimodalArrayWithoutTypeKey()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        // Array without 'type' key - stored as array, normalized by provider
        $nonMultimodalContent = [
            'First line',
            'Second line',
            'Third line'
        ];
        
        $result = $builder
            ->message('user', $nonMultimodalContent)
            ->run();
        
        $this->assertTrue($result['success']);
        // Content is array in params (provider's prepareRequestBody will normalize it)
        $this->assertIsArray($provider->lastParams['messages'][0]['content']);
        $this->assertCount(3, $provider->lastParams['messages'][0]['content']);
    }
    
    public function testMultimodalContentPreservesStructure()
    {
        $provider = new MultimodalProvider();
        $builder = new TaskBuilder($provider);
        
        $originalContent = [
            ['type' => 'text', 'text' => 'Analyze receipt'],
            ['type' => 'image_url', 'image_url' => [
                'url' => 'data:image/jpeg;base64,RECEIPT_DATA',
                'detail' => 'high'
            ]]
        ];
        
        $result = $builder
            ->message('user', $originalContent)
            ->run();
        
        $this->assertTrue($result['success']);
        $sentContent = $provider->lastParams['messages'][0]['content'];
        
        // Verify structure is preserved exactly
        $this->assertEquals($originalContent, $sentContent);
    }
}
