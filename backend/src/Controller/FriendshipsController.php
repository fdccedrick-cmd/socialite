<?php
declare(strict_types=1);

namespace App\Controller;

use App\Utility\NotificationHelper;
use App\Utility\WebSocketClient;

/**
 * Friendships Controller
 *
 * @property \App\Model\Table\FriendshipsTable $Friendships
 * @property \App\Model\Table\UsersTable $Users
 */
class FriendshipsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
        
        $this->Friendships = $this->fetchTable('Friendships');
        $this->Users = $this->fetchTable('Users');
    }

    /**
     * Get current user ID from authentication
     */
    private function getCurrentUserId()
    {
        $identity = $this->Authentication->getIdentity();
        
        if (!$identity) {
            return null;
        }

        $userId = null;
        if (is_object($identity)) {
            if (method_exists($identity, 'getOriginalData')) {
                $orig = $identity->getOriginalData();
                if (is_object($orig) && isset($orig->id)) {
                    $userId = $orig->id;
                } elseif (is_array($orig) && isset($orig['id'])) {
                    $userId = $orig['id'];
                }
            } elseif (isset($identity->id)) {
                $userId = $identity->id;
            }
        } elseif (is_array($identity) && isset($identity['id'])) {
            $userId = $identity['id'];
        }

        return $userId;
    }

    /**
     * List all friends
     */
    public function index()
    {
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            $this->Flash->error('Please log in to view your friends.');
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $friendships = $this->Friendships->getFriends($userId);
        
        $friends = [];
        foreach ($friendships as $friendship) {
            if ($friendship->user_id == $userId) {
                $friend = $friendship->friend;
                $friendId = $friendship->friend_id;
            } else {
                $friend = $friendship->user;
                $friendId = $friendship->user_id;
            }

            $mutualCount = $this->Friendships->getMutualFriendsCount($userId, $friendId);

            $friends[] = [
                'id' => $friendId,
                'full_name' => $friend->full_name,
                'username' => $friend->username,
                'profile_photo_path' => $friend->profile_photo_path,
                'mutual_friends_count' => $mutualCount,
            ];
        }

     
        $searchQuery = $this->request->getQuery('search');
        if ($searchQuery) {
            $searchQuery = trim($searchQuery);
            $friends = array_filter($friends, function($friend) use ($searchQuery) {
                return stripos($friend['full_name'], $searchQuery) !== false || 
                       stripos($friend['username'], $searchQuery) !== false;
            });
        }

        $this->set(compact('friends', 'searchQuery'));
    }

    /**
     * List friend requests
     */
    public function requests()
    {
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            $this->Flash->error('Please log in to view friend requests.');
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $pendingRequests = $this->Friendships->getPendingRequests($userId);
        
        $requests = [];
        foreach ($pendingRequests as $request) {
            $sender = $request->user;
            
            $mutualCount = $this->Friendships->getMutualFriendsCount($userId, $request->user_id);

            $requests[] = [
                'friendship_id' => $request->id,
                'id' => $sender->id,
                'full_name' => $sender->full_name,
                'username' => $sender->username,
                'profile_photo_path' => $sender->profile_photo_path,
                'mutual_friends_count' => $mutualCount,
                'created' => $request->created,
            ];
        }

        $this->set(compact('requests'));
    }

    /**
     * List friend suggestions (users who are not friends yet)
     */
    public function suggestions()
    {
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            $this->Flash->error('Please log in to view friend suggestions.');
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $friendIds = [];
        $friendships = $this->Friendships->find()
            ->select(['user_id', 'friend_id'])
            ->where([
                'OR' => [
                    'user_id' => $userId,
                    'friend_id' => $userId,
                ],
                'status' => 'accepted',
            ])
            ->all();

        foreach ($friendships as $friendship) {
            if ($friendship->user_id == $userId) {
                $friendIds[] = $friendship->friend_id;
            } else {
                $friendIds[] = $friendship->user_id;
            }
        }

        $pendingIds = [];
        $pendingRequests = $this->Friendships->find()
            ->select(['user_id', 'friend_id'])
            ->where([
                'OR' => [
                    'user_id' => $userId,
                    'friend_id' => $userId,
                ],
                'status' => 'pending',
            ])
            ->all();

        foreach ($pendingRequests as $request) {
            if ($request->user_id == $userId) {
                $pendingIds[] = $request->friend_id;
            } else {
                $pendingIds[] = $request->user_id;
            }
        }

        $excludeIds = array_merge($friendIds, $pendingIds, [$userId]);
        
        $suggestions = $this->Users->find()
            ->where(['id NOT IN' => $excludeIds])
            ->all();

        $suggestionList = [];
        foreach ($suggestions as $user) {
           
            $mutualCount = $this->Friendships->getMutualFriendsCount($userId, $user->id);

           
            if ($mutualCount > 0) {
                $suggestionList[] = [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'profile_photo_path' => $user->profile_photo_path,
                    'mutual_friends_count' => $mutualCount,
                ];
            }
        }

        usort($suggestionList, function($a, $b) {
            return $b['mutual_friends_count'] - $a['mutual_friends_count'];
        });

        $this->set('suggestions', $suggestionList);
    }

    /**
     * Send friend request (API endpoint)
     */
    public function add()
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');
        
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Unauthorized']));
        }

        $friendId = $this->request->getData('friend_id');
        
        if (!$friendId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Friend ID is required']));
        }

      
        $existingFriendship = $this->Friendships->getFriendshipStatus($userId, $friendId);
        
        if ($existingFriendship) {
            $message = 'Friend request already exists';
            if ($existingFriendship->status === 'accepted') {
                $message = 'You are already friends';
            }
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['success' => false, 'message' => $message]));
        }

        $friendship = $this->Friendships->newEntity([
            'user_id' => $userId,
            'friend_id' => $friendId,
            'status' => 'pending',
        ]);

        if ($this->Friendships->save($friendship)) {
            NotificationHelper::createFriendRequestNotification($friendId, $userId);

            try {
                $ws = WebSocketClient::getInstance();
                $ws->broadcastFriendshipChange('added', $userId, $friendId, $friendship->id);
            } catch (\Exception $e) {
                error_log('WebSocket notification failed: ' . $e->getMessage());
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Friend request sent',
                    'friendship_id' => $friendship->id,
                ]));
        } else {
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Failed to send friend request',
                    'errors' => $friendship->getErrors(),
                ]));
        }
    }

    /**
     * Accept friend request (API endpoint)
     */
    public function accept($friendshipId = null)
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');
        
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Unauthorized']));
        }

        if (!$friendshipId) {
            $friendshipId = $this->request->getData('friendship_id');
        }

        if (!$friendshipId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Friendship ID is required']));
        }

        $friendship = $this->Friendships->get($friendshipId);
        
        if ($friendship->friend_id != $userId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(403)
                ->withStringBody(json_encode(['success' => false, 'message' => 'You cannot accept this request']));
        }

        $friendship->status = 'accepted';
        
        if ($this->Friendships->save($friendship)) {
            NotificationHelper::createFriendAcceptNotification($friendship->user_id, $userId);

            try {
                $ws = WebSocketClient::getInstance();
                $ws->broadcastFriendshipChange('accepted', $userId, $friendship->user_id, $friendship->id);
            } catch (\Exception $e) {
                error_log('WebSocket notification failed: ' . $e->getMessage());
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Friend request accepted',
                ]));
        } else {
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Failed to accept friend request',
                ]));
        }
    }

    /**
     * Reject friend request (API endpoint)
     */
    public function reject($friendshipId = null)
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');
        
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Unauthorized']));
        }

        if (!$friendshipId) {
            $friendshipId = $this->request->getData('friendship_id');
        }

        if (!$friendshipId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Friendship ID is required']));
        }

        $friendship = $this->Friendships->get($friendshipId);
        

        if ($friendship->friend_id != $userId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(403)
                ->withStringBody(json_encode(['success' => false, 'message' => 'You cannot reject this request']));
        }

        if ($this->Friendships->delete($friendship)) {
            try {
                $ws = WebSocketClient::getInstance();
                $ws->notifyFriendRequestRejected($friendship->user_id, $userId);
            } catch (\Exception $e) {
                error_log('WebSocket notification failed: ' . $e->getMessage());
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Friend request rejected',
                ]));
        } else {
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Failed to reject friend request',
                ]));
        }
    }

    /**
     * Remove friend (unfriend) or cancel friend request (API endpoint)
     */
    public function remove()
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');
        
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Unauthorized']));
        }

        $friendId = $this->request->getData('friend_id');
        
        if (!$friendId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Friend ID is required']));
        }
        $friendship = $this->Friendships->find()
            ->where([
                'OR' => [
                    [
                        'user_id' => $userId,
                        'friend_id' => $friendId,
                    ],
                    [
                        'user_id' => $friendId,
                        'friend_id' => $userId,
                        'status' => 'accepted' 
                    ],
                ],
            ])
            ->first();

        if (!$friendship) {
            return $this->response
                ->withType('application/json')
                ->withStatus(404)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Friendship not found']));
        }

        if ($this->Friendships->delete($friendship)) {
            $message = $friendship->status === 'pending' ? 'Friend request cancelled' : 'Friend removed';
            
            // If cancelling a pending request, delete the notification
            if ($friendship->status === 'pending') {
                $otherUserId = ($friendship->user_id == $userId) ? $friendship->friend_id : $friendship->user_id;
                NotificationHelper::deleteFriendRequestNotification($otherUserId, $userId);
            }
            
            // Send WebSocket notification
            try {
                $ws = WebSocketClient::getInstance();
                $action = $friendship->status === 'pending' ? 'cancelled' : 'removed';
                $otherUserId = ($friendship->user_id == $userId) ? $friendship->friend_id : $friendship->user_id;
                $ws->broadcastFriendshipChange($action, $userId, $otherUserId);
            } catch (\Exception $e) {
                error_log('WebSocket notification failed: ' . $e->getMessage());
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => $message,
                ]));
        } else {
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Failed to remove friend',
                ]));
        }
    }

    /**
     * Check friendship status (API endpoint)
     */
    public function status($friendId = null)
    {
        $this->request->allowMethod(['get']);
        $this->viewBuilder()->setClassName('Json');
        
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Unauthorized']));
        }

        if (!$friendId) {
            $friendId = $this->request->getQuery('friend_id');
        }

        if (!$friendId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Friend ID is required']));
        }

        $friendship = $this->Friendships->getFriendshipStatus($userId, $friendId);
        
        if ($friendship) {
            $isSender = $friendship->user_id == $userId;
            
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'status' => $friendship->status,
                    'is_sender' => $isSender,
                    'friendship_id' => $friendship->id,
                ]));
        } else {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'status' => 'none',
                ]));
        }
    }
}
