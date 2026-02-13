<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\ORM\TableRegistry;

/**
 * Notification Helper Utility
 * 
 * Provides easy methods to create notifications throughout the app
 */
class NotificationHelper
{
    /**
     * Create a notification
     *
     * @param int $userId User receiving the notification
     * @param int $actorId User who triggered the notification
     * @param string $type Type of notification (like, comment, follow, etc.)
     * @param string $notifiableType The model type (Post, Comment, User)
     * @param int $notifiableId The ID of the notifiable item
     * @param string $message The notification message
     * @return bool Success
     */
    public static function create(
        int $userId,
        int $actorId,
        string $type,
        string $notifiableType,
        int $notifiableId,
        string $message
    ): bool {
        // Don't notify yourself
        if ($userId === $actorId) {
            return false;
        }

        $notificationsTable = TableRegistry::getTableLocator()->get('Notifications');
        
        $notification = $notificationsTable->newEntity([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiableId,
            'message' => $message,
            'is_read' => false,
        ]);

        return (bool) $notificationsTable->save($notification);
    }

    /**
     * Create a "like" notification
     *
     * @param int $postOwnerId Owner of the post
     * @param int $likerId User who liked
     * @param int $postId Post ID
     * @param string $likerName Name of the person who liked
     * @return bool
     */
    public static function like(int $postOwnerId, int $likerId, int $postId, string $likerName): bool
    {
        return self::create(
            $postOwnerId,
            $likerId,
            'like',
            'Post',
            $postId,
            "<strong>" . htmlspecialchars($likerName) . "</strong> liked your post"
        );
    }

    /**
     * Create a "comment" notification
     *
     * @param int $postOwnerId Owner of the post
     * @param int $commenterId User who commented
     * @param int $postId Post ID
     * @param string $commenterName Name of the person who commented
     * @return bool
     */
    public static function comment(int $postOwnerId, int $commenterId, int $postId, string $commenterName): bool
    {
        return self::create(
            $postOwnerId,
            $commenterId,
            'comment',
            'Post',
            $postId,
            "<strong>" . htmlspecialchars($commenterName) . "</strong> commented on your post"
        );
    }

    /**
     * Create a "comment like" notification
     *
     * @param int $commentOwnerId Owner of the comment
     * @param int $likerId User who liked the comment
     * @param int $postId Post ID (for navigation)
     * @param int $commentId Comment ID
     * @param string $likerName Name of the person who liked
     * @return bool
     */
    public static function commentLike(int $commentOwnerId, int $likerId, int $postId, int $commentId, string $likerName): bool
    {
        return self::create(
            $commentOwnerId,
            $likerId,
            'comment_like',
            'Comment',
            $commentId,
            "<strong>" . htmlspecialchars($likerName) . "</strong> liked your comment"
        );
    }

    /**
     * Create a "reply" notification
     *
     * @param int $commentOwnerId Owner of the comment
     * @param int $replierId User who replied
     * @param int $commentId Comment ID
     * @param string $replierName Name of the person who replied
     * @return bool
     */
    public static function reply(int $commentOwnerId, int $replierId, int $commentId, string $replierName): bool
    {
        return self::create(
            $commentOwnerId,
            $replierId,
            'reply',
            'Comment',
            $commentId,
            "<strong>" . htmlspecialchars($replierName) . "</strong> replied to your comment"
        );
    }

    /**
     * Create a "follow" notification
     *
     * @param int $followedUserId User being followed
     * @param int $followerId User who followed
     * @param string $followerName Name of the follower
     * @return bool
     */
    public static function follow(int $followedUserId, int $followerId, string $followerName): bool
    {
        return self::create(
            $followedUserId,
            $followerId,
            'follow',
            'User',
            $followerId,
            "<strong>" . htmlspecialchars($followerName) . "</strong> started following you"
        );
    }
}
