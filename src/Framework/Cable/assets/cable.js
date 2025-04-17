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
                    lastId: 0
                };
            }
            
            return {
                on: (event, callback) => {
                    if (!this.subscriptions[channel].events[event]) {
                        this.subscriptions[channel].events[event] = [];
                    }
                    
                    this.subscriptions[channel].events[event].push(callback);
                    return this;
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
                            return this;
                        }
                    };
                }
            };
        }
        
        /**
         * Start polling for updates
         */
        startPolling() {
            if (!this.connected) return;
            
            this.poll();
            
            this.pollingInterval = setInterval(() => {
                this.poll();
            }, this.options.pollInterval);
        }
        
        /**
         * Poll for updates
         */
        poll() {
            const channels = Object.keys(this.subscriptions);
            
            if (channels.length === 0) return;
            
            channels.forEach(channel => {
                const subscription = this.subscriptions[channel];
                
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
                        console.error('Cable polling error:', error);
                        this.handleError();
                    });
            });
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
        handleError() {
            this.reconnectAttempts++;
            
            if (this.reconnectAttempts <= this.options.maxReconnectAttempts) {
                // Exponential backoff
                const delay = this.options.reconnectInterval * Math.pow(2, this.reconnectAttempts - 1);
                
                console.log(`Cable reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);
                
                setTimeout(() => {
                    this.poll();
                }, delay);
            } else {
                console.error('Cable max reconnect attempts reached');
                this.disconnect();
            }
        }
        
        /**
         * Disconnect from the server
         */
        disconnect() {
            this.connected = false;
            clearInterval(this.pollingInterval);
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
            // Store channel-specific interval
            if (!this.subscriptions[channel]) {
                this.subscriptions[channel] = {
                    events: {},
                    lastId: 0,
                    pollInterval: interval
                };
            } else {
                this.subscriptions[channel].pollInterval = interval;
            }
        }
    }
    
    // Export to window
    window.cable = {
        connect: function(options) {
            return new Cable(options).connect();
        }
    };
})(window);
