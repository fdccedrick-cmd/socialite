/**
 * WebSocket Manager for Real-time Updates
 * Handles WebSocket connection and message broadcasting
 */
class WebSocketManager {
    constructor(userId) {
        this.userId = userId;
        this.ws = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 10;
        this.reconnectDelay = 3000;
        this.messageHandlers = [];
        this.pingInterval = null;
    }

    connect() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            return;
        }

        const wsUrl = `ws://${window.location.hostname}:8081`;
        console.log('[WebSocket] Connecting to:', wsUrl);

        try {
            this.ws = new WebSocket(wsUrl);

            this.ws.onopen = () => {
                console.log('[WebSocket] Connected');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                
                // Register user with server
                this.send({
                    action: 'register',
                    user_id: this.userId
                });

                // Start ping interval to keep connection alive
                this.startPingInterval();

                // Notify handlers
                this.notifyHandlers({ type: 'connection', status: 'connected' });
            };

            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    console.log('[WebSocket] Message received:', data);
                    this.notifyHandlers(data);
                } catch (error) {
                    console.error('[WebSocket] Failed to parse message:', error);
                }
            };

            this.ws.onerror = (error) => {
                console.error('[WebSocket] Error:', error);
                this.isConnected = false;
            };

            this.ws.onclose = () => {
                console.log('[WebSocket] Disconnected');
                this.isConnected = false;
                this.stopPingInterval();
                this.notifyHandlers({ type: 'connection', status: 'disconnected' });
                
                // Attempt to reconnect
                if (this.reconnectAttempts < this.maxReconnectAttempts) {
                    this.reconnectAttempts++;
                    console.log(`[WebSocket] Reconnecting in ${this.reconnectDelay/1000}s (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
                    setTimeout(() => this.connect(), this.reconnectDelay);
                } else {
                    console.error('[WebSocket] Max reconnect attempts reached');
                }
            };
        } catch (error) {
            console.error('[WebSocket] Connection failed:', error);
        }
    }

    disconnect() {
        this.stopPingInterval();
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
        this.isConnected = false;
    }

    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
            return true;
        }
        console.warn('[WebSocket] Cannot send, not connected');
        return false;
    }

    startPingInterval() {
        this.stopPingInterval();
        this.pingInterval = setInterval(() => {
            if (this.isConnected) {
                this.send({ action: 'ping' });
            }
        }, 30000); // Ping every 30 seconds
    }

    stopPingInterval() {
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
            this.pingInterval = null;
        }
    }

    addMessageHandler(handler) {
        if (typeof handler === 'function') {
            this.messageHandlers.push(handler);
        }
    }

    removeMessageHandler(handler) {
        const index = this.messageHandlers.indexOf(handler);
        if (index > -1) {
            this.messageHandlers.splice(index, 1);
        }
    }

    notifyHandlers(data) {
        this.messageHandlers.forEach(handler => {
            try {
                handler(data);
            } catch (error) {
                console.error('[WebSocket] Handler error:', error);
            }
        });
    }
}

// Make it globally available
window.WebSocketManager = WebSocketManager;
