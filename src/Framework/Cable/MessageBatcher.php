<?php

namespace Lightpack\Cable;

/**
 * Message Batcher
 * 
 * This class batches multiple messages to reduce database writes.
 */
class MessageBatcher
{
    /**
     * @var Cable
     */
    protected $cable;
    
    /**
     * @var string
     */
    protected $channel;
    
    /**
     * @var array
     */
    protected $messages = [];
    
    /**
     * @var int
     */
    protected $batchSize;
    
    /**
     * Create a new message batcher
     */
    public function __construct(Cable $cable, string $channel, int $batchSize = 100)
    {
        $this->cable = $cable;
        $this->channel = $channel;
        $this->batchSize = $batchSize;
        
        // Register shutdown function to ensure messages are sent
        register_shutdown_function([$this, 'flush']);
    }
    
    /**
     * Add a message to the batch
     */
    public function add(string $event, array $payload): self
    {
        $this->messages[] = [
            'event' => $event,
            'payload' => $payload
        ];
        
        // Flush if batch size reached
        if (count($this->messages) >= $this->batchSize) {
            $this->flush();
        }
        
        return $this;
    }
    
    /**
     * Flush all messages in the batch
     */
    public function flush(): void
    {
        if (empty($this->messages)) {
            return;
        }
        
        // Send as a single batch event
        $this->cable->to($this->channel)->emit('batch', [
            'events' => $this->messages,
            'count' => count($this->messages),
            'timestamp' => time()
        ]);
        
        // Clear batch
        $this->messages = [];
    }
}
