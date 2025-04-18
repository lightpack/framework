# Cable: Real-Time Communication for Lightpack

Cable is Lightpack's elegant solution for real-time communication between your server and clients. With a Socket.io-like API and a focus on simplicity and efficiency, Cable provides powerful real-time features without external dependencies.

## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Channel-Based Communication](#channel-based-communication)
- [Event Handling](#event-handling)
- [DOM Updates](#dom-updates)
- [Presence Channels](#presence-channels)
- [Performance Optimizations](#performance-optimizations)
- [Configuration](#configuration)
- [Security Considerations](#security-considerations)
- [Advanced Usage](#advanced-usage)

## Overview

Cable uses an efficient polling mechanism to provide real-time communication capabilities:

- **Socket.io-like API**: Familiar, event-based programming model
- **Channel-based messaging**: Target specific users or groups
- **Presence channels**: Track which users are online
- **DOM updates**: Directly update page elements
- **Driver architecture**: Support for database and Redis backends

Unlike WebSockets, Cable works with any hosting environment and doesn't require special server configurations.

## Installation

Cable is included with Lightpack by default. The JavaScript client is available at `/js/cable.js` and can be included in your views:

```php
<?= asset()->load('js/cable.js') ?>
```

## Basic Usage

### Client-Side

```javascript
// Connect to Cable
const socket = cable.connect();

// Subscribe to a channel
socket.subscribe('notifications', {
    // Handle events
    'new-message': function(data) {
        console.log('New message:', data.text);
    }
});
```

### Server-Side

```php
// Get the Cable instance
$cable = app()->resolve('cable');

// Emit an event to a channel
$cable->to('notifications')->emit('new-message', [
    'text' => 'Hello, world!',
    'timestamp' => time()
]);
```

## Channel-Based Communication

Channels allow you to organize your real-time communication:

```php
// Send to a specific user
$cable->to("user.{$userId}")->emit('private-message', [
    'text' => 'This is a private message'
]);

// Send to a group
$cable->to('admin-notifications')->emit('system-alert', [
    'level' => 'warning',
    'message' => 'Disk space is low'
]);

// Broadcast to everyone
$cable->to('broadcasts.all')->emit('announcement', [
    'message' => 'Site maintenance in 10 minutes'
]);
```

## Event Handling

### Subscribing to Events

```javascript
socket.subscribe('my-channel', {
    // Single event handler
    'event-name': function(data) {
        console.log('Event received:', data);
    }
});

// Or add handlers later
const subscription = socket.subscribe('another-channel');
subscription.on('event-one', function(data) {
    console.log('Event one:', data);
});
subscription.on('event-two', function(data) {
    console.log('Event two:', data);
});
```

### Emitting Events

```php
// Basic event
$cable->to('channel-name')->emit('event-name', [
    'key' => 'value'
]);

// Event with multiple properties
$cable->to('dashboard')->emit('stats-update', [
    'users' => $activeUsers,
    'cpu' => $cpuUsage,
    'memory' => $memoryUsage
]);
```

## DOM Updates

Cable can directly update DOM elements without writing custom JavaScript:

```php
// Update a specific element by selector
$cable->to('dashboard')->update('#user-count', "<strong>{$userCount}</strong> users online");

// Update multiple elements with the same selector
$cable->to('dashboard')->update('.status-indicator', '<span class="online"></span>');
```

Client-side, this is handled automatically - no additional code needed!

## Presence Channels

Presence channels allow you to track which users are online in real-time.

### Client-Side

```javascript
// Connect to Cable
const socket = cable.connect();

// Subscribe to presence channel
socket.subscribe('presence-room', {
    // Handle presence updates
    'presence:update': function(data) {
        console.log('Users online:', data.users);
        updateUsersList(data.users);
    },
    
    // Handle join events
    'presence:join': function(data) {
        console.log('Users joined:', data.users);
        showNotification(`${data.users.length} user(s) joined`);
    },
    
    // Handle leave events
    'presence:leave': function(data) {
        console.log('Users left:', data.users);
        showNotification(`${data.users.length} user(s) left`);
    }
});

// Join presence channel
socket.presence('presence-room', userId).join()
    .then(() => {
        console.log('Joined presence channel');
        
        // Start heartbeat to maintain presence
        socket.presence('presence-room', userId).startHeartbeat();
    });

// Leave presence channel
socket.presence('presence-room', userId).stopHeartbeat();
socket.presence('presence-room', userId).leave()
    .then(() => {
        console.log('Left presence channel');
    });

// Get users in channel
socket.presence('presence-room').getUsers()
    .then(data => {
        console.log('Users in channel:', data.users);
    });
```

### Server-Side

```php
// Get the Presence instance
$presence = app()->resolve('presence');

// Join a channel
$presence->join($userId, 'presence-room');

// Leave a channel
$presence->leave($userId, 'presence-room');

// Get users in a channel
$users = $presence->getUsers('presence-room');

// Get channels a user is in
$channels = $presence->getChannels($userId);

// Clean up stale presence records
$presence->cleanup();
```

### Setting Up Routes

```php
// Cable polling route
route()->get('/cable/poll', CableController::class, 'poll')->name('cable.poll');

// Presence channel routes
route()->post('/cable/presence/join', PresenceController::class, 'join')->name('cable.presence.join')->filter('csrf');
route()->post('/cable/presence/leave', PresenceController::class, 'leave')->name('cable.presence.leave')->filter('csrf');
route()->post('/cable/presence/heartbeat', PresenceController::class, 'heartbeat')->name('cable.presence.heartbeat')->filter('csrf');
route()->post('/cable/presence/users', PresenceController::class, 'users')->name('cable.presence.users')->filter('csrf');
```

## Performance Optimizations

Cable includes several optimizations for high-performance applications:

### Channel Grouping

Group channels to reduce database load:

```php
// Server-side
$channelManager = app()->resolve('cable.channels');
$channelManager->group('chat-rooms', ['room-1', 'room-2', 'room-3']);

// Emit to all channels in the group
$channelManager->emitToGroup('chat-rooms', 'new-message', [
    'text' => 'Hello, everyone!'
]);
```

### Message Batching

Batch multiple messages for fewer database writes:

```php
// Server-side
$batcher = app()->resolve('cable.batcher');

// Add messages to the batch
$batcher->add('channel-1', 'event-1', ['data' => 'value-1']);
$batcher->add('channel-1', 'event-2', ['data' => 'value-2']);
$batcher->add('channel-2', 'event-3', ['data' => 'value-3']);

// Flush the batch (sends all messages at once)
$batcher->flush();
```

### Client-Side Batching

```javascript
// Enable client-side batching
socket.batch.configure({
    size: 10,           // Max events per batch
    interval: 2000,     // Flush interval in ms
    endpoint: '/api/batch-events'
});

// Track events (added to batch)
socket.batch.track('event-1', { data: 'value-1' });
socket.batch.track('event-2', { data: 'value-2' });

// Manually flush batch
socket.batch.flush();
```

## Configuration

### Server-Side

Configure Cable in `config/cable.php`:

```php
return [
    // Driver (database or redis)
    'driver' => 'database',
    
    // Database configuration
    'database' => [
        'table' => 'cable_messages',
        'cleanup_older_than' => 86400, // 24 hours
    ],
    
    // Redis configuration
    'redis' => [
        'prefix' => 'cable:',
        'cleanup_older_than' => 86400, // 24 hours
    ],
    
    // Presence configuration
    'presence' => [
        'driver' => 'database',
        'timeout' => 60, // seconds
        'database' => [
            'table' => 'cable_presence'
        ],
        'redis' => [
            'prefix' => 'presence:'
        ]
    ]
];
```

### Client-Side Configuration

Cable.js provides extensive configuration options to customize its behavior:

#### Basic Connection Options

```javascript
const socket = cable.connect({
    // Core settings
    endpoint: '/cable/poll',           // Polling endpoint URL
    pollInterval: 3000,                // Polling interval in milliseconds (default: 3000)
    reconnectInterval: 5000,           // Reconnection attempt interval in milliseconds (default: 5000)
    maxReconnectAttempts: 5,           // Maximum reconnection attempts (default: 5)
    
    // Advanced settings
    autoReconnect: true,               // Automatically attempt to reconnect when disconnected (default: true)
    connectImmediately: true,          // Start polling immediately upon connection (default: true)
    debug: false                        // Enable debug logging to console (default: false)
});
```

#### Batch Configuration

Configure client-side event batching:

```javascript
// Configure batch settings
socket.batch.configure({
    size: 10,                          // Maximum number of events in a batch (default: 10)
    interval: 5000,                    // Batch flush interval in milliseconds (default: 5000)
    endpoint: '/api/batch-events',     // Endpoint for batch events (default: '/api/batch-events')
    csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
});
```

#### Presence Channel Configuration

```javascript
// Join a presence channel with custom settings
socket.presence(channel, userId).join('/custom/join/endpoint');

// Start heartbeat with custom interval and endpoint
socket.presence(channel, userId).startHeartbeat(
    30000,                             // Heartbeat interval in milliseconds (default: 20000)
    '/custom/heartbeat/endpoint'       // Custom heartbeat endpoint
);

// Get users with custom endpoint
socket.presence(channel).getUsers('/custom/users/endpoint');
```

#### Subscription Options

```javascript
// Subscribe with custom options
socket.subscribe(channel, {
    // Event handlers
    'event-name': function(data) {
        console.log('Event received:', data);
    }
}, {
    backfill: true,                    // Fetch historical messages on subscribe (default: false)
    backfillLimit: 50,                 // Maximum number of historical messages (default: 20)
    filter: function(message) {        // Custom filter function for incoming messages
        return message.priority > 5;   // Only process high-priority messages
    }
});
```

#### Global Configuration

Set global defaults for all Cable instances:

```javascript
// Set global defaults before creating any connections
cable.defaults = {
    endpoint: '/my-custom-endpoint',
    pollInterval: 10000,
    reconnectInterval: 15000,
    maxReconnectAttempts: 10,
    debug: true
};

// All new connections will use these defaults
const socket = cable.connect();
```

#### Advanced Connection Management

```javascript
// Manually control connection state
const socket = cable.connect({ connectImmediately: false });

// Start connection manually
socket.connect();

// Disconnect
socket.disconnect();

// Reconnect with reset state
socket.reconnect(true);

// Check connection status
if (socket.connected) {
    console.log('Connected to Cable server');
}

// Get connection statistics
const stats = socket.getStats();
console.log(`Messages received: ${stats.messagesReceived}`);
console.log(`Failed polls: ${stats.failedPolls}`);
```

#### Event Filtering and Transformation

```javascript
// Global message transformer
socket.setTransformer(function(message) {
    // Add timestamp to all messages
    message.receivedAt = Date.now();
    return message;
});

// Global message filter
socket.setFilter(function(message) {
    // Filter out debug messages in production
    return message.event.indexOf('debug:') !== 0;
});
```

#### Error Handling

```javascript
// Set global error handler
socket.onError(function(error) {
    console.error('Cable error:', error);
    
    // Log to server
    fetch('/log-error', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ error: error.message })
    });
});

// Set reconnection handler
socket.onReconnect(function(attempt) {
    console.log(`Reconnection attempt ${attempt}`);
    showToast(`Reconnecting... (Attempt ${attempt})`);
});

// Set connection lost handler
socket.onConnectionLost(function() {
    showOfflineBanner();
});

// Set connection restored handler
socket.onConnectionRestored(function() {
    hideOfflineBanner();
    showToast('Connection restored!');
});
```

These configuration options provide extensive flexibility to customize Cable's behavior for your specific application needs.

## Security Considerations

### CSRF Protection

Always protect your Cable routes with CSRF protection:

```php
route()->get('/cable/poll', CableController::class, 'poll')->filter('csrf');
```

### Authentication

Secure your channels by authenticating users:

```php
// In a middleware or controller
if (!auth()->check()) {
    return response()->json(['error' => 'Unauthorized'], 401);
}

$userId = auth()->user()->id;
$channel = request()->input('channel');

// Check channel authorization
if (strpos($channel, "user.{$userId}") !== 0 && strpos($channel, 'public.') !== 0) {
    return response()->json(['error' => 'Forbidden'], 403);
}
```

### Presence Channel Security

Validate user identity for presence channels:

```php
public function join(Request $request, Response $response)
{
    // Ensure user is authenticated
    if (!auth()->check()) {
        return $response->json(['error' => 'Unauthorized'], 401);
    }
    
    // Only allow using own user ID
    $authenticatedUserId = auth()->user()->id;
    $requestedUserId = $request->input('userId');
    
    if ($authenticatedUserId != $requestedUserId) {
        return $response->json(['error' => 'Cannot use another user\'s ID'], 403);
    }
    
    // Continue with join...
}
```

## Advanced Usage

### Custom Drivers

Create custom drivers by implementing the `DriverInterface`:

```php
namespace App\Cable;

use Lightpack\Cable\DriverInterface;

class CustomDriver implements DriverInterface
{
    public function emit(string $channel, string $event, array $payload): void
    {
        // Custom implementation
    }
    
    public function getMessages(string $channel, ?int $lastId = null): array
    {
        // Custom implementation
    }
    
    public function cleanup(int $olderThanSeconds = 86400): void
    {
        // Custom implementation
    }
}
```

Register your custom driver:

```php
// In a service provider
$this->app->bind('cable.driver', function($app) {
    return new \App\Cable\CustomDriver();
});
```

### Integration with External Services

Cable can be integrated with external services:

```php
// Push notifications when emitting events
$cable->to('notifications')->emit('new-message', [
    'text' => 'Hello, world!'
]);

// In a listener or middleware
event()->listen('cable.emit', function($channel, $event, $payload) {
    if ($channel === 'notifications' && $event === 'new-message') {
        // Send push notification
        $pushService->send($payload['text']);
    }
});
```

---

Cable provides a simple yet powerful way to add real-time features to your Lightpack applications. With its focus on simplicity, efficiency, and flexibility, you can quickly implement chat applications, live dashboards, notifications, and more without the complexity of WebSockets or external dependencies.
