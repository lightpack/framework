<?php

namespace Lightpack\Cable\Controllers;

use Lightpack\Cable\Presence;

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
    public function join()
    {
        $userId = request()->input('userId');
        $channel = request()->input('channel');
        
        if (empty($userId) || empty($channel)) {
            return response()->json(['error' => 'User ID and channel are required'], 400);
        }
        
        $this->presence->join($userId, $channel);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Leave a presence channel
     */
    public function leave()
    {
        $userId = request()->input('userId');
        $channel = request()->input('channel');
        
        if (empty($userId) || empty($channel)) {
            return response()->json(['error' => 'User ID and channel are required'], 400);
        }
        
        $this->presence->leave($userId, $channel);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Send a heartbeat to keep presence active
     */
    public function heartbeat()
    {
        $userId = request()->input('userId');
        $channel = request()->input('channel');
        
        if (empty($userId) || empty($channel)) {
            return response()->json(['error' => 'User ID and channel are required'], 400);
        }
        
        $this->presence->heartbeat($userId, $channel);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Get users present in a channel
     */
    public function users()
    {
        $channel = request()->input('channel');
        
        if (empty($channel)) {
            return response()->json(['error' => 'Channel is required'], 400);
        }
        
        $users = $this->presence->getUsers($channel);
        
        return response()->json([
            'users' => $users,
            'count' => count($users)
        ]);
    }
}
