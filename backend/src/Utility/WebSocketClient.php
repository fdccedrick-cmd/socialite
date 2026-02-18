<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * WebSocket Client Utility
 * Sends messages to WebSocket server from PHP controllers
 */
class WebSocketClient
{
    private static $instance = null;
    private $host;
    private $port;

    private function __construct()
    {
        $this->host = '127.0.0.1';
        $this->port = 8081;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Broadcast message to all WebSocket clients
     */
    public function broadcast(array $data): bool
    {
        return $this->sendToServer($data);
    }

    /**
     * Send message to specific user
     */
    public function sendToUser(int $userId, array $data): bool
    {
        $message = array_merge($data, ['target_user' => $userId]);
        return $this->sendToServer($message);
    }

    /**
     * Internal method to send data to WebSocket server
     */
    private function sendToServer(array $data): bool
    {
        try {
            // Use HTTP to communicate with WebSocket server
            // In production, consider using a message queue (Redis, RabbitMQ)
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($data),
                    'timeout' => 1
                ]
            ]);

            // For now, we'll store in a shared file that WebSocket server can read
            // This is a simple approach; for production use Redis or similar
            $this->writeToQueue($data);
            
            return true;
        } catch (\Exception $e) {
            error_log("WebSocket broadcast failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Write message to a queue file for WebSocket server to process
     * Simple file-based approach for development
     */
    private function writeToQueue(array $data): void
    {
        $queueFile = TMP . 'websocket_queue.json';
        $queue = [];
        
        if (file_exists($queueFile)) {
            $content = file_get_contents($queueFile);
            $queue = json_decode($content, true) ?: [];
        }
        
        $queue[] = [
            'timestamp' => time(),
            'data' => $data
        ];
        
        // Keep only last 100 messages
        if (count($queue) > 100) {
            $queue = array_slice($queue, -100);
        }
        
        file_put_contents($queueFile, json_encode($queue));
    }

    /**
     * Notify about new post
     */
    public function notifyNewPost(int $postId, int $userId, string $userName): bool
    {
        return $this->broadcast([
            'type' => 'new_post',
            'post_id' => $postId,
            'user_id' => $userId,
            'user_name' => $userName,
            'timestamp' => time()
        ]);
    }

    /**
     * Notify about new like
     */
    public function notifyLike(string $targetType, int $targetId, int $userId, string $userName, int $postOwnerId): bool
    {
        // Broadcast to all for live update
        $this->broadcast([
            'type' => 'like_added',
            'target_type' => $targetType,
            'target_id' => $targetId,
            'user_id' => $userId,
            'timestamp' => time()
        ]);

        // Send notification to post owner
        if ($postOwnerId !== $userId) {
            return $this->sendToUser($postOwnerId, [
                'type' => 'notification',
                'notification_type' => 'like',
                'message' => "{$userName} liked your post",
                'target_type' => $targetType,
                'target_id' => $targetId,
                'timestamp' => time()
            ]);
        }

        return true;
    }

    /**
     * Notify about new comment
     */
    public function notifyComment(int $postId, int $commentId, int $userId, string $userName, int $postOwnerId, ?int $postImageId = null): bool
    {
        // Broadcast to all for live update
        $broadcastData = [
            'type' => 'comment_added',
            'post_id' => $postId,
            'comment_id' => $commentId,
            'user_id' => $userId,
            'timestamp' => time()
        ];
        
        if ($postImageId !== null) {
            $broadcastData['post_image_id'] = $postImageId;
        }
        
        $this->broadcast($broadcastData);

        // Send notification to post owner
        if ($postOwnerId !== $userId) {
            return $this->sendToUser($postOwnerId, [
                'type' => 'notification',
                'notification_type' => 'comment',
                'message' => "{$userName} commented on your post",
                'post_id' => $postId,
                'comment_id' => $commentId,
                'timestamp' => time()
            ]);
        }

        return true;
    }

    /**
     * Notify about unlike
     */
    public function notifyUnlike(string $targetType, int $targetId, int $userId): bool
    {
        return $this->broadcast([
            'type' => 'like_removed',
            'target_type' => $targetType,
            'target_id' => $targetId,
            'user_id' => $userId,
            'timestamp' => time()
        ]);
    }

    /**
     * Notify about deleted comment
     */
    public function notifyCommentDeleted(int $postId, int $commentId): bool
    {
        return $this->broadcast([
            'type' => 'comment_deleted',
            'post_id' => $postId,
            'comment_id' => $commentId,
            'timestamp' => time()
        ]);
    }

    /**
     * Notify about friend request
     */
    public function notifyFriendRequest(int $recipientId, int $senderId, string $senderName): bool
    {
        return $this->sendToUser($recipientId, [
            'type' => 'notification',
            'notification_type' => 'friend_request',
            'message' => "{$senderName} sent you a friend request",
            'sender_id' => $senderId,
            'timestamp' => time()
        ]);
    }

    /**
     * Notify about friend request accepted
     */
    public function notifyFriendRequestAccepted(int $recipientId, int $accepterId, string $accepterName): bool
    {
        return $this->sendToUser($recipientId, [
            'type' => 'notification',
            'notification_type' => 'friend_accepted',
            'message' => "{$accepterName} accepted your friend request",
            'accepter_id' => $accepterId,
            'timestamp' => time()
        ]);
    }

    /**
     * Broadcast friendship status change
     */
    public function broadcastFriendshipChange(string $action, int $userId, int $friendId, ?int $friendshipId = null): bool
    {
        $data = [
            'type' => 'friendship_change',
            'action' => $action, // 'added', 'accepted', 'cancelled', 'rejected', 'removed'
            'user_id' => $userId,
            'friend_id' => $friendId,
            'timestamp' => time()
        ];

        if ($friendshipId !== null) {
            $data['friendship_id'] = $friendshipId;
        }

        // Send to both users
        $this->sendToUser($userId, $data);
        $this->sendToUser($friendId, $data);

        return true;
    }

    /**
     * Notify about friend request cancelled
     */
    public function notifyFriendRequestCancelled(int $recipientId, int $senderId): bool
    {
        return $this->sendToUser($recipientId, [
            'type' => 'friendship_change',
            'action' => 'cancelled',
            'user_id' => $senderId,
            'friend_id' => $recipientId,
            'timestamp' => time()
        ]);
    }

    /**
     * Notify about friend request rejected
     */
    public function notifyFriendRequestRejected(int $senderId, int $recipientId): bool
    {
        return $this->sendToUser($senderId, [
            'type' => 'friendship_change',
            'action' => 'rejected',
            'user_id' => $recipientId,
            'friend_id' => $senderId,
            'timestamp' => time()
        ]);
    }

    /**
     * Notify about friend removed (unfriended)
     */
    public function notifyFriendRemoved(int $userId, int $friendId): bool
    {
        return $this->sendToUser($friendId, [
            'type' => 'friendship_change',
            'action' => 'removed',
            'user_id' => $userId,
            'friend_id' => $friendId,
            'timestamp' => time()
        ]);
    }
}
