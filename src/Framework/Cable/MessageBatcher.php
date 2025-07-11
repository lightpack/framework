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
     * @var string|null
     */
    protected $channel = null;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var int
     */
    protected $batchSize = 100;
    
    /**
     * Create a new message batcher.
     *
     * @param Cable $cable
     */
    public function __construct(Cable $cable)
    {
        $this->cable = $cable;
        // Register shutdown function to ensure messages are sent
        register_shutdown_function([$this, 'flush']);
    }

    /**
     * Set the channel for batching.
     *
     * @param string $channel
     * @return self
     */
    public function channel(string $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * Set the batch size.
     *
     * @param int $size
     * @return self
     */
    public function batchSize(int $size): self
    {
        $this->batchSize = $size;
        return $this;
    }
    
    /**
     * Add a message to the batch
     *
     * @param string $event
     * @param array $payload
     * @return self
     * @throws \LogicException
     */
    public function add(string $event, array $payload): self
    {
        if ($this->channel === null) {
            throw new \LogicException('Channel must be set before adding messages.');
        }
        if ($this->batchSize <= 0) {
            throw new \LogicException('Batch size must be greater than zero.');
        }
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
     *
     * @throws \LogicException
     */
    public function flush(): void
    {
        if (empty($this->messages)) {
            return;
        }
        if ($this->channel === null) {
            throw new \LogicException('Channel must be set before flushing messages.');
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
