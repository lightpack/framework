<?php

namespace Lightpack\Cable;

use Lightpack\Http\Request;
use Lightpack\Http\Response;

/**
 * Presence Controller
 * 
 * This controller handles presence channel operations
 * such as joining, leaving, and heartbeats.
 */
class PresenceController
{
    /**
     * @var Presence
     */
    protected $presence;
    
    /**
     * Create a new controller instance
     */
    public function __construct(Presence $presence)
    {
        $this->presence = $presence;
    }
    
    /**
     * Join a presence channel
     */
    public function join(Request $request, Response $response)
    {
        $userId = $request->input('userId');
        $channel = $request->input('channel');
        
        if (empty($userId) || empty($channel)) {
            return $response->json(['error' => 'User ID and channel are required'], 400);
        }
        
        $this->presence->join($userId, $channel);
        
        return $response->json(['success' => true]);
    }
    
    /**
     * Leave a presence channel
     */
    public function leave(Request $request, Response $response)
    {
        $userId = $request->input('userId');
        $channel = $request->input('channel');
        
        if (empty($userId) || empty($channel)) {
            return $response->json(['error' => 'User ID and channel are required'], 400);
        }
        
        $this->presence->leave($userId, $channel);
        
        return $response->json(['success' => true]);
    }
    
    /**
     * Send a heartbeat to keep presence active
     */
    public function heartbeat(Request $request, Response $response)
    {
        $userId = $request->input('userId');
        $channel = $request->input('channel');
        
        if (empty($userId) || empty($channel)) {
            return $response->json(['error' => 'User ID and channel are required'], 400);
        }
        
        $this->presence->heartbeat($userId, $channel);
        
        return $response->json(['success' => true]);
    }
    
    /**
     * Get users present in a channel
     */
    public function users(Request $request, Response $response)
    {
        $channel = $request->input('channel');
        
        if (empty($channel)) {
            return $response->json(['error' => 'Channel is required'], 400);
        }
        
        $users = $this->presence->getUsers($channel);
        
        return $response->json([
            'users' => $users,
            'count' => count($users)
        ]);
    }
    
    /**
     * Clean up stale presence records and broadcast updates
     */
    public function cleanup(Response $response)
    {
        $removed = $this->presence->cleanup();
        
        return $response->json([
            'success' => true,
            'removed' => $removed
        ]);
    }
}
