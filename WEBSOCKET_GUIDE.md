# WebSocket Implementation Guide

## 📡 Real-time Updates with Native WebSocket

### What's Included

✅ **WebSocket Server** (Ratchet PHP)
- Real-time broadcasting to all connected clients
- User-specific notifications
- Connection management with auto-reconnect
- Ping/pong keepalive

✅ **Backend Integration**
- PostsController: Broadcasts new posts
- LikesController: Broadcasts likes/unlikes
- CommentsController: Broadcasts new comments
- WebSocketClient utility for easy broadcasting

✅ **Frontend Integration**  
- WebSocketManager class for connection management
- Auto-reconnect with exponential backoff
- Real-time UI updates without page reload
- Dashboard integration ready

---

## 🚀 Setup Instructions

### 1. Install Dependencies

```bash
# Enter the backend container
docker compose exec backend sh

# Install Ratchet
composer require cboden/ratchet

# Make WebSocket script executable
chmod +x bin/websocket-server.php
chmod +x bin/start-websocket.sh
```

### 2. Start the WebSocket Server

The WebSocket server starts automatically with Docker Compose now on port 8081.

**Manual start (if needed):**
```bash
docker compose exec backend php bin/websocket-server.php
```

### 3. Restart Docker Services

```bash
docker compose down
docker compose up -d
```

### 4. Verify WebSocket is Running

```bash
# Check if WebSocket server is listening
docker compose exec backend netstat -tulpn | grep 8081

# View WebSocket logs
docker compose logs -f backend | grep WebSocket
```

---

## 🎯 How It Works

### Real-time Events

1. **New Post Created** → All users see notification
2. **Post Liked** → Like count updates instantly
3. **Post Unliked** → Like count decreases instantly
4. **Comment Added** → Comment count updates, new comment appears
5. **Friend Request** → Notification sent to recipient
6. **Friend Accepted** → Notification sent to requester

### Message Flow

```
User Action (Like/Comment/Post)
    ↓
Controller processes request
    ↓
WebSocketClient broadcasts event
    ↓
WebSocket Server receives message
    ↓
Broadcasts to all connected clients
    ↓
Frontend receives message
    ↓
UI updates automatically
```

---

## 📝 Usage Examples

### Broadcasting from PHP Controller

```php
use App\Utility\WebSocketClient;

// Broadcast new post
$ws = WebSocketClient::getInstance();
$ws->notifyNewPost($postId, $userId, $userName);

// Broadcast like
$ws->notifyLike('Post', $postId, $userId, $userName, $postOwnerId);

// Broadcast comment
$ws->notifyComment($postId, $commentId, $userId, $userName, $postOwnerId);

// Send notification to specific user
$ws->sendToUser($recipientId, [
    'type' => 'notification',
    'message' => 'Custom notification'
]);
```

### Handling in Frontend JavaScript

```javascript
// Already integrated in dashboard.js
handleWebSocketMessage(data) {
    switch(data.type) {
        case 'like_added':
            // Update like count
            break;
        case 'comment_added':
            // Reload comments
            break;
        case 'notification':
            // Show notification
            break;
    }
}
```

---

## 🔧 Configuration

### WebSocket Server Port
- Default: `8081`
- Change in: `backend/bin/websocket-server.php` and `docker-compose.yaml`

### Frontend Connection
- URL: `ws://localhost:8081`
- Auto-configured in `websocket-manager.js`

---

## 🐛 Troubleshooting

### WebSocket Won't Connect

1. **Check if server is running:**
   ```bash
   docker compose exec backend ps aux | grep websocket
   ```

2. **View logs:**
   ```bash
   docker compose logs -f backend | grep -i websocket
   ```

3. **Restart WebSocket server:**
   ```bash
   docker compose restart backend
   ```

### Browser Console Errors

Check browser console (F12) for:
```
[WebSocket] Connecting to: ws://localhost:8081
[WebSocket] Connected
[WebSocket] User registered
```

### Port Already in Use

If port 8081 is already in use, change it in:
- `docker-compose.yaml` (ports section)
- `backend/bin/websocket-server.php` (line with $port =)
- `backend/webroot/js/websocket-manager.js` (WebSocket URL)

---

## 📊 Monitoring

### Active Connections
```bash
docker compose exec backend netstat -an | grep 8081
```

### WebSocket Logs
```bash
tail -f backend/logs/websocket.log
```

### Queue Messages (Debug)
```bash
cat backend/tmp/websocket_queue.json | jq .
```

---

## 🚀 Production Considerations

1. **Use a Process Manager**
   - Supervisor or PM2 to keep WebSocket server running
   - Auto-restart on failure

2. **Load Balancing**
   - For multiple servers, use Redis Pub/Sub
   - Replace file-based queue with Redis

3. **Security**
   - Add authentication tokens
   - Use WSS (WebSocket Secure) with SSL
   - Validate all incoming messages

4. **Scaling**
   - Consider Socket.IO for easier scaling
   - Use Redis adapter for multi-server setup

---

## ✅ Testing

1. Open dashboard in two browser windows
2. Like a post in one window
3. See the like count update instantly in the other window
4. Post a comment and watch it appear in real-time
5. Check browser console for WebSocket messages

---

## 📚 Next Steps

- [ ] Add authentication to WebSocket connections
- [ ] Implement online/offline user status
- [ ] Add typing indicators for comments
- [ ] Real-time chat between friends
- [ ] Notification sound effects

---

🎉 **Real-time updates are now active!** Your Socialite app now supports instant notifications and live updates across all connected users.
