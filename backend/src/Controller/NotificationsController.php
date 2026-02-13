<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Notifications Controller
 *
 * @property \App\Model\Table\NotificationsTable $Notifications
 */
class NotificationsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
    }

    /**
     * Get all notifications for logged-in user
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $identity = $this->Authentication->getIdentity();
        
        if (!$identity) {
            $this->Flash->error('Please login to view notifications');
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $userId = $identity->getOriginalData()->id;

        $notifications = $this->Notifications->find()
            ->where(['Notifications.user_id' => $userId])
            ->contain(['Actors' => ['fields' => ['id', 'username', 'full_name', 'profile_photo_path']]])
            ->order(['Notifications.created' => 'DESC'])
            ->limit(50)
            ->all();

        // Mark all as read when viewing
        $this->Notifications->updateAll(
            ['is_read' => true, 'read_at' => new \DateTime()],
            ['user_id' => $userId, 'is_read' => false]
        );

        $this->set(compact('notifications'));
        $this->set('user', $identity->getOriginalData());
    }

    /**
     * Get unread notification count (AJAX/API)
     *
     * @return \Cake\Http\Response|null|void JSON response
     */
    public function count()
    {
        $this->request->allowMethod(['get']);
        $identity = $this->Authentication->getIdentity();
        
        if (!$identity) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode(['count' => 0]));
        }

        $userId = $identity->getOriginalData()->id;

        $count = $this->Notifications->find()
            ->where([
                'user_id' => $userId,
                'is_read' => false
            ])
            ->count();

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['count' => $count]));
    }

    /**
     * API: Get recent notifications (last 10)
     *
     * @return \Cake\Http\Response JSON response
     */
    public function recent()
    {
        $this->request->allowMethod(['get']);
        
        try {
            $identity = $this->Authentication->getIdentity();
            if (!$identity) {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(401)
                    ->withStringBody(json_encode(['error' => 'Unauthorized']));
            }
            
            $userId = $identity->getOriginalData()->id;
            
            // Get recent notifications with actor user data
            $notifications = $this->Notifications->find()
                ->where(['Notifications.user_id' => $userId])
                ->contain(['Actors'])
                ->order(['Notifications.created' => 'DESC'])
                ->limit(10)
                ->toArray();
            
            // Format notifications with actor avatar
            $formattedNotifications = array_map(function ($notif) {
                // Default avatar - use pravatar as fallback
                $actorAvatar = 'https://i.pravatar.cc/150?img=' . ($notif->actor_id % 70 + 1);
                
                // Check if actor exists and has profile_photo_path
                if (isset($notif->actor) && !empty($notif->actor->profile_photo_path)) {
                    // Use the profile_photo_path directly (it's already a full path from DB)
                    $actorAvatar = $notif->actor->profile_photo_path;
                }
                
                return [
                    'id' => $notif->id,
                    'message' => $notif->message,
                    'type' => $notif->type,
                    'notifiable_type' => $notif->notifiable_type,
                    'notifiable_id' => $notif->notifiable_id,
                    'is_read' => $notif->is_read,
                    'created' => $notif->created->toIso8601String(),
                    'actor_avatar' => $actorAvatar,
                    'actor_username' => isset($notif->actor) ? ($notif->actor->username ?? 'Unknown') : 'Unknown',
                    'actor_id' => $notif->actor_id
                ];
            }, $notifications);
            
            // Count unread
            $unreadCount = $this->Notifications->find()
                ->where([
                    'user_id' => $userId,
                    'is_read' => false
                ])
                ->count();
            
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'notifications' => $formattedNotifications,
                    'unreadCount' => $unreadCount
                ]));
                
        } catch (\Exception $e) {
            return $this->response
                ->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]));
        }
    }

    /**
     * Mark a single notification as read
     *
     * @param string|null $id Notification id.
     * @return \Cake\Http\Response|null Redirects or JSON
     */
    public function markAsRead($id = null)
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $identity = $this->Authentication->getIdentity();
        
        if (!$identity) {
            // Check if AJAX/API request
            if ($this->request->is('ajax') || $this->request->accepts('application/json')) {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(401)
                    ->withStringBody(json_encode(['error' => 'Unauthorized']));
            }
            
            $this->Flash->error('Unauthorized');
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $userId = $identity->getOriginalData()->id;

        try {
            $notification = $this->Notifications->get($id);
            
            // Ensure user owns this notification
            if ($notification->user_id !== $userId) {
                if ($this->request->is('ajax') || $this->request->accepts('application/json')) {
                    return $this->response
                        ->withType('application/json')
                        ->withStatus(403)
                        ->withStringBody(json_encode(['error' => 'Forbidden']));
                }
                
                $this->Flash->error('You cannot access this notification');
                return $this->redirect(['action' => 'index']);
            }

            $notification->is_read = true;
            $notification->read_at = new \DateTime();
            
            if ($this->Notifications->save($notification)) {
                // AJAX/API response
                if ($this->request->is('ajax') || $this->request->accepts('application/json')) {
                    return $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode(['success' => true]));
                }
                
                $this->Flash->success('Notification marked as read');
            } else {
                if ($this->request->is('ajax') || $this->request->accepts('application/json')) {
                    return $this->response
                        ->withType('application/json')
                        ->withStatus(500)
                        ->withStringBody(json_encode(['error' => 'Failed to update']));
                }
            }
        } catch (\Exception $e) {
            if ($this->request->is('ajax') || $this->request->accepts('application/json')) {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(500)
                    ->withStringBody(json_encode(['error' => $e->getMessage()]));
            }
            
            $this->Flash->error('Notification not found');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Mark all notifications as read
     *
     * @return \Cake\Http\Response|null Redirects or JSON
     */
    public function markAllAsRead()
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $identity = $this->Authentication->getIdentity();
        
        if (!$identity) {
            // Check if AJAX/API request
            if ($this->request->is('ajax') || $this->request->accepts('application/json')) {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(401)
                    ->withStringBody(json_encode(['error' => 'Unauthorized']));
            }
            
            $this->Flash->error('Unauthorized');
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $userId = $identity->getOriginalData()->id;

        try {
            $updated = $this->Notifications->updateAll(
                ['is_read' => true, 'read_at' => new \DateTime()],
                ['user_id' => $userId, 'is_read' => false]
            );

            // AJAX/API response
            if ($this->request->is('ajax') || $this->request->accepts('application/json')) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'updated' => $updated
                    ]));
            }

            $this->Flash->success(__('Marked {0} notifications as read', $updated));
        } catch (\Exception $e) {
            if ($this->request->is('ajax') || $this->request->accepts('application/json')) {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(500)
                    ->withStringBody(json_encode(['error' => $e->getMessage()]));
            }
            
            $this->Flash->error('Failed to update notifications');
        }
        
        return $this->redirect(['action' => 'index']);
    }

    /**
     * Delete a notification
     *
     * @param string|null $id Notification id.
     * @return \Cake\Http\Response|null Redirects
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Authentication->getIdentity();
        
        if (!$user) {
            $this->Flash->error('Unauthorized');
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $notification = $this->Notifications->get($id);
        
        // Ensure user owns this notification
        if ($notification->user_id !== $user->id) {
            $this->Flash->error('You cannot delete this notification');
            return $this->redirect(['action' => 'index']);
        }

        if ($this->Notifications->delete($notification)) {
            $this->Flash->success('Notification deleted');
        } else {
            $this->Flash->error('Failed to delete notification');
        }

        return $this->redirect(['action' => 'index']);
    }
}
