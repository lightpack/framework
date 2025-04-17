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
        subscribe(channel) {
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
    }
    
    // Export to window
    window.cable = {
        connect: function(options) {
            return new Cable(options).connect();
        }
    };
})(window);
