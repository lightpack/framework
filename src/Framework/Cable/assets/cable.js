/**
 * Lightpack Cable - Client-side library
 * 
 * This library provides a Socket.io-like API for real-time communication
 * using efficient polling.
 */
(function(window) {
    'use strict';
    
    /**
     * Cable client
     */
    class Cable {
        constructor(options = {}) {
            this.options = Object.assign({
                endpoint: '/cable/poll',
                pollInterval: 3000,
                reconnectInterval: 5000,
                maxReconnectAttempts: 5
            }, options);
            
            this.connected = false;
            this.reconnectAttempts = 0;
            this.lastIds = {};
            this.subscriptions = {};
            this.pollingIntervals = {};
            
            // --- Outgoing Event Batching Support ---
            this._outgoingBatch = [];
            this._batchSize = 10; // Default batch size
            this._batchInterval = 5000; // Default flush interval in ms
            this._batchEndpoint = '/api/batch-events'; // Default API endpoint
            this._batchIntervalId = null;
            this._csrfToken = null;
        }
        
        /**
         * Connect to the server
         */
        connect() {
            this.connected = true;
            this.startPolling();
            return this;
        }
        
        /**
         * Subscribe to a channel
         */
        subscribe(channel, handlers = {}) {
            if (!this.subscriptions[channel]) {
                this.subscriptions[channel] = {
                    events: {},
                    lastId: 0,
                    pollInterval: this.options.pollInterval,
                    lastPollTime: 0
                };
                
                // Start polling for this channel
                this.startChannelPolling(channel);
            }
            
            // Handle object-style event handlers
            if (typeof handlers === 'object' && handlers !== null) {
                Object.keys(handlers).forEach(event => {
                    if (typeof handlers[event] === 'function') {
                        if (!this.subscriptions[channel].events[event]) {
                            this.subscriptions[channel].events[event] = [];
                        }
                        this.subscriptions[channel].events[event].push(handlers[event]);
                    }
                });
            }
            
            const subscription = {
                on: (event, callback) => {
                    if (!this.subscriptions[channel].events[event]) {
                        this.subscriptions[channel].events[event] = [];
                    }
                    
                    this.subscriptions[channel].events[event].push(callback);
                    return subscription; // Return subscription object for chaining
                },
                filter: (filterFn) => {
                    this.subscriptions[channel].filter = filterFn;
                    
                    return {
                        on: (event, callback) => {
                            if (!this.subscriptions[channel].events[event]) {
                                this.subscriptions[channel].events[event] = [];
                            }
                            
                            // Wrap callback with filter
                            const wrappedCallback = (payload) => {
                                if (this.subscriptions[channel].filter(payload)) {
                                    callback(payload);
                                }
                            };
                            
                            this.subscriptions[channel].events[event].push(wrappedCallback);
                            return subscription; // Return subscription object for chaining
                        }
                    };
                }
            };
            
            return subscription;
        }
        
        /**
         * Start polling for updates
         */
        startPolling() {
            if (!this.connected) return;
            
            // Start polling for each channel
            Object.keys(this.subscriptions).forEach(channel => {
                this.startChannelPolling(channel);
            });
        }
        
        /**
         * Start polling for a specific channel
         */
        startChannelPolling(channel) {
            if (!this.connected || !this.subscriptions[channel]) return;
            
            // Clear any existing interval
            if (this.pollingIntervals[channel]) {
                clearInterval(this.pollingIntervals[channel]);
            }
            
            // Poll immediately
            this.pollChannel(channel);
            
            // Set up interval for this channel
            const interval = this.subscriptions[channel].pollInterval;
            this.pollingIntervals[channel] = setInterval(() => {
                this.pollChannel(channel);
            }, interval);
        }
        
        /**
         * Poll for updates for a specific channel
         */
        pollChannel(channel) {
            if (!this.connected || !this.subscriptions[channel]) return;
            
            const subscription = this.subscriptions[channel];
            subscription.lastPollTime = Date.now();
            
            fetch(`${this.options.endpoint}?channel=${encodeURIComponent(channel)}&lastId=${subscription.lastId}`)
                .then(response => {
                    if (response.status === 304) {
                        return []; // No new messages
                    }
                    return response.json();
                })
                .then(messages => {
                    if (messages.length > 0) {
                        // Update last ID
                        subscription.lastId = messages[messages.length - 1].id;
                        
                        // Process messages
                        messages.forEach(message => {
                            this.processMessage(channel, message);
                        });
                    }
                    
                    // Reset reconnect attempts on success
                    this.reconnectAttempts = 0;
                })
                .catch(error => {
                    console.error(`Cable polling error for channel ${channel}:`, error);
                    this.handleError(channel);
                });
        }
        
        /**
         * Poll for updates (legacy method, now just polls all channels)
         */
        poll() {
            const channels = Object.keys(this.subscriptions);
            channels.forEach(channel => this.pollChannel(channel));
        }
        
        /**
         * Process a message
         */
        processMessage(channel, message) {
            const subscription = this.subscriptions[channel];
            const event = message.event;
            const payload = message.payload || {};
            
            // Special handling for DOM updates
            if (event === 'dom-update' && payload.selector && payload.html) {
                const elements = document.querySelectorAll(payload.selector);
                elements.forEach(el => {
                    el.innerHTML = payload.html;
                });
            }
            
            // Handle batch events
            if (event === 'batch' && Array.isArray(payload.events)) {
                payload.events.forEach(batchEvent => {
                    this.triggerEvent(channel, batchEvent.event, batchEvent.payload);
                });
                return;
            }
            
            // Handle presence updates
            if (event === 'presence:update' && Array.isArray(payload.users)) {
                // Store previous users list for join/leave detection
                const previousUsers = this._getPresenceUsers(channel) || [];
                
                // Update presence state
                this._setPresenceUsers(channel, payload.users);
                
                // Detect joins and leaves
                const joined = payload.users.filter(id => !previousUsers.includes(id));
                const left = previousUsers.filter(id => !payload.users.includes(id));
                
                // Trigger presence events
                if (joined.length > 0) {
                    this.triggerEvent(channel, 'presence:join', { users: joined });
                }
                
                if (left.length > 0) {
                    this.triggerEvent(channel, 'presence:leave', { users: left });
                }
            }
            
            // Call event handlers
            this.triggerEvent(channel, event, payload);
        }
        
        /**
         * Trigger event handlers
         */
        triggerEvent(channel, event, payload) {
            const subscription = this.subscriptions[channel];
            
            if (subscription.events[event]) {
                subscription.events[event].forEach(callback => {
                    callback(payload);
                });
            }
        }
        
        /**
         * Handle connection error
         */
        handleError(channel) {
            this.reconnectAttempts++;
            
            if (this.reconnectAttempts <= this.options.maxReconnectAttempts) {
                // Exponential backoff
                const delay = this.options.reconnectInterval * Math.pow(2, this.reconnectAttempts - 1);
                
                console.log(`Cable reconnecting channel ${channel} in ${delay}ms (attempt ${this.reconnectAttempts})`);
                
                setTimeout(() => {
                    this.pollChannel(channel);
                }, delay);
            } else {
                console.error(`Cable max reconnect attempts reached for channel ${channel}`);
                this.disconnectChannel(channel);
            }
        }
        
        /**
         * Disconnect from the server
         */
        disconnect() {
            this.connected = false;
            
            // Clear all polling intervals
            Object.keys(this.pollingIntervals).forEach(channel => {
                clearInterval(this.pollingIntervals[channel]);
                delete this.pollingIntervals[channel];
            });
        }
        
        /**
         * Disconnect a specific channel
         */
        disconnectChannel(channel) {
            if (this.pollingIntervals[channel]) {
                clearInterval(this.pollingIntervals[channel]);
                delete this.pollingIntervals[channel];
            }
        }
        
        /**
         * Configure polling intervals for different channels
         */
        configure(channelConfig) {
            this.channelConfig = channelConfig;
            
            // Apply configuration
            Object.keys(channelConfig).forEach(channelPattern => {
                const config = channelConfig[channelPattern];
                
                // Find matching channels
                Object.keys(this.subscriptions).forEach(channel => {
                    if (channel.startsWith(channelPattern)) {
                        // Apply configuration
                        if (config.pollInterval) {
                            this.setPollInterval(channel, config.pollInterval);
                        }
                    }
                });
            });
        }
        
        /**
         * Set polling interval for a channel
         */
        setPollInterval(channel, interval) {
            if (!this.subscriptions[channel]) {
                this.subscriptions[channel] = {
                    events: {},
                    lastId: 0,
                    pollInterval: interval,
                    lastPollTime: 0
                };
            } else {
                this.subscriptions[channel].pollInterval = interval;
            }
            
            // Restart polling with new interval
            this.startChannelPolling(channel);
        }
        
        // --- Presence Channel Support ---
        
        /**
         * Get users present in a channel
         */
        getPresenceUsers(channel) {
            return this._getPresenceUsers(channel) || [];
        }
        
        /**
         * Check if a user is present in a channel
         */
        isUserPresent(channel, userId) {
            const users = this._getPresenceUsers(channel);
            return users ? users.includes(userId) : false;
        }
        
        /**
         * Internal: Get presence users from channel state
         */
        _getPresenceUsers(channel) {
            if (!this.subscriptions[channel]) return null;
            if (!this.subscriptions[channel].presence) return null;
            return this.subscriptions[channel].presence.users;
        }
        
        /**
         * Internal: Set presence users in channel state
         */
        _setPresenceUsers(channel, users) {
            if (!this.subscriptions[channel]) return;
            if (!this.subscriptions[channel].presence) {
                this.subscriptions[channel].presence = {};
            }
            this.subscriptions[channel].presence.users = users;
        }
        
        // --- Outgoing Event Batching Support ---
        
        _initBatching() {
            if (this._batchIntervalId) return; // Prevent multiple intervals
            this._batchIntervalId = setInterval(() => {
                this.flushOutgoingBatch();
            }, this._batchInterval);
        }
        
        emitBatched(channel, event, payload) {
            this._outgoingBatch.push({ channel, event, payload });
            if (this._outgoingBatch.length >= this._batchSize) {
                this.flushOutgoingBatch();
            }
            this._initBatching();
        }
        
        flushOutgoingBatch() {
            if (this._outgoingBatch.length === 0) return;
            // Determine CSRF token: use option, then meta tag, else error
            let csrfToken = this._csrfToken;
            if (!csrfToken) {
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) {
                    csrfToken = meta.getAttribute('content');
                }
            }
            if (!csrfToken) {
                console.error('CSRF token not found. Cannot send batch POST.');
                return;
            }
            fetch(this._batchEndpoint, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ events: this._outgoingBatch }),
            })
            .catch(err => {
                // Optionally handle errors (could retry, etc.)
                console.error('Failed to send batched events:', err);
            });
            this._outgoingBatch = [];
        }
        
        setBatchOptions({ batchSize, batchInterval, batchEndpoint, csrfToken } = {}) {
            if (typeof batchSize === 'number') this._batchSize = batchSize;
            if (typeof batchInterval === 'number') {
                this._batchInterval = batchInterval;
                if (this._batchIntervalId) {
                    clearInterval(this._batchIntervalId);
                    this._batchIntervalId = null;
                }
                this._initBatching();
            }
            if (typeof batchEndpoint === 'string') this._batchEndpoint = batchEndpoint;
            if (typeof csrfToken === 'string') this._csrfToken = csrfToken;
        }
    }
    
    // Export to window
    window.cable = {
        connect: function(options) {
            return new Cable(options).connect();
        }
    };
})(window);
