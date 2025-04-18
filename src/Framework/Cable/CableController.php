<?php

namespace Lightpack\Cable;

use Lightpack\Http\Request;
use Lightpack\Http\Response;

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
    public function poll(Request $request, Response $response)
    {
        $channel = $request->input('channel');
        $lastId = (int) $request->input('lastId', 0);
        
        if (empty($channel)) {
            return $response->json(['error' => 'Channel is required'], 400);
        }
        
        $messages = $this->cable->getMessages($channel, $lastId);
        
        if (empty($messages)) {
            return $response->json([], 304); // Not Modified
        }
        
        return $response->json($messages);
    }
}
