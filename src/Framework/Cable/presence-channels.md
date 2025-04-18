# Presence Channels in Lightpack Cable

Presence channels allow you to track which users are online in real-time channels, enabling features like online user lists, typing indicators, and join/leave notifications.

## Setup

### 1. Run the Migration

First, run the migration to create the `cable_presence` table:

```bash
php lightpack migrate create_cable_presence_table
```

### 2. Register the Provider

Make sure the CableProvider is registered in your `App.php` file:

```php
// src/App.php
public function getFrameworkProviders()
{
    return [
        // ... other providers
        \Lightpack\Providers\RedisProvider::class, // If using Redis driver
        \Lightpack\Cable\CableProvider::class,
    ];
}
```

## Basic Usage

### Backend (PHP)

```php
// Get Cable instance
$cable = app()->resolve('cable');

// Create a presence driver (Database or Redis)
$driver = new \Lightpack\Cable\Drivers\DatabasePresenceDriver($db);
// OR
$driver = new \Lightpack\Cable\Drivers\RedisPresenceDriver($redis);

// Create Presence instance
$presence = new \Lightpack\Cable\Presence($cable, $driver);

// Track user joining a channel
$presence->join($userId, 'room-1');

// Track user leaving a channel
$presence->leave($userId, 'room-1');

// Send heartbeat to keep user presence active
$presence->heartbeat($userId, 'room-1');

// Get users present in a channel
$users = $presence->getUsers('room-1');

// Get channels a user is present in
$channels = $presence->getChannels($userId);

// Clean up stale presence records (not needed for Redis driver)
$presence->cleanup();
```

### Frontend (JavaScript)

```js
// Initialize Cable
const cable = window.cable.connect();

// Subscribe to a presence channel
cable.subscribe('room-1', {
    // Handle presence updates
    'presence:update': function(data) {
        console.log('Users online:', data.users);
        console.log('User count:', data.count);
        
        // Update UI with online users
        updateOnlineUsersList(data.users);
    },
    
    // Handle other events
    'chatMessage': function(data) {
        // Handle chat messages
    }
});
```

## Common Use Cases

### Online User Lists

Display which users are currently online in a channel:

```js
function updateOnlineUsersList(users) {
    const userList = document.getElementById('online-users');
    userList.innerHTML = '';
    
    users.forEach(userId => {
        const userItem = document.createElement('li');
        userItem.textContent = `User ${userId} is online`;
        userList.appendChild(userItem);
    });
    
    // Update count
    document.getElementById('user-count').textContent = users.length;
}
```

### Join/Leave Notifications

Detect when users join or leave by comparing previous and current user lists:

```js
let previousUsers = [];

cable.subscribe('room-1', {
    'presence:update': function(data) {
        // Find new users (joined)
        const joinedUsers = data.users.filter(userId => !previousUsers.includes(userId));
        
        // Find users who left
        const leftUsers = previousUsers.filter(userId => !data.users.includes(userId));
        
        // Show notifications
        joinedUsers.forEach(userId => {
            showNotification(`User ${userId} joined the room`);
        });
        
        leftUsers.forEach(userId => {
            showNotification(`User ${userId} left the room`);
        });
        
        // Update previous users list
        previousUsers = [...data.users];
    }
});
```

### Typing Indicators

Implement typing indicators with presence channels:

```js
// Backend
$presence->join($userId, 'typing:room-1');
// When user stops typing
$presence->leave($userId, 'typing:room-1');

// Frontend
cable.subscribe('typing:room-1', {
    'presence:update': function(data) {
        if (data.users.length > 0) {
            showTypingIndicator(`${data.users.length} user(s) typing...`);
        } else {
            hideTypingIndicator();
        }
    }
});
```

## Driver Configuration

### Database Driver

```php
$driver = new \Lightpack\Cable\Drivers\DatabasePresenceDriver($db);

// Customize table name
$driver->setTable('custom_presence_table');

// Set timeout (in seconds)
$driver->setTimeout(60); // 1 minute
```

### Redis Driver

```php
$driver = new \Lightpack\Cable\Drivers\RedisPresenceDriver($redis);

// Customize key prefix
$driver->setPrefix('myapp:presence:');

// Set timeout (in seconds)
$driver->setTimeout(60); // 1 minute
```

## Performance Considerations

- For small to medium applications, the DatabaseDriver is sufficient.
- For high-traffic applications with thousands of concurrent users, use the RedisDriver.
- Run `$presence->cleanup()` periodically (e.g., via a cron job) to remove stale presence records when using the DatabaseDriver.
- Redis automatically handles expiry via TTL, so cleanup is not needed.
