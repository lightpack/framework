<?php

namespace Lightpack\Cable\Controllers;

use Lightpack\Cable\Cable;

/**
 * Cable Controller
 * 
 * This controller handles polling requests from clients
 * for real-time communication.
 */
class CableController
{
    /**
     * @var Cable
     */
    protected $cable;
    
    /**
     * Create a new controller instance
     */
    public function __construct(Cable $cable)
    {
        $this->cable = $cable;
    }
    
    /**
     * Poll for new messages
     */
    public function poll()
    {
        $channel = request()->input('channel');
        $lastId = (int) request()->input('lastId', 0);
        
        if (empty($channel)) {
            return response()->json(['error' => 'Channel is required'], 400);
        }
        
        $messages = $this->cable->getMessages($channel, $lastId);
        
        if (empty($messages)) {
            return response()->json([], 304); // Not Modified
        }
        
        return response()->json($messages);
    }
}
