<?php

namespace Lightpack\Cable;

/**
 * Cable Driver Interface
 * 
 * This interface defines the methods that all Cable drivers must implement.
 */
interface CableDriverInterface
{
    /**
     * Emit an event to a channel
     * 
     * @param string $channel The channel to emit to
     * @param string $event The event name
     * @param array $payload The event payload
     * @return void
     */
    public function emit(string $channel, string $event, array $payload): void;
    
    /**
     * Get messages from a channel
     * 
     * @param string $channel The channel to get messages from
     * @param int|null $lastId The last message ID received
     * @return array The messages
     */
    public function getMessages(string $channel, ?int $lastId = null): array;
    
    /**
     * Clean up old messages
     * 
     * @param int $olderThanSeconds Delete messages older than this many seconds
     * @return void
     */
    public function cleanup(int $olderThanSeconds = 86400): void;
}
