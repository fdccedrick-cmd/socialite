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
        $user = $this->Authentication->getIdentity();
        
        if (!$user) {
            $this->Flash->error('Please login to view notifications');
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $notifications = $this->Notifications->find()
            ->where(['Notifications.user_id' => $user->id])
            ->contain(['Actors' => ['fields' => ['id', 'username', 'full_name', 'profile_photo_path']]])
            ->order(['Notifications.created' => 'DESC'])
            ->limit(50)
            ->all();

        // Mark all as read when viewing
        $this->Notifications->updateAll(
            ['is_read' => true, 'read_at' => new \DateTime()],
            ['user_id' => $user->id, 'is_read' => false]
        );

        $this->set(compact('notifications'));
    }

    /**
     * Get unread notification count (AJAX/API)
     *
     * @return \Cake\Http\Response|null|void JSON response
     */
    public function count()
    {
        $this->request->allowMethod(['get']);
        $user = $this->Authentication->getIdentity();
        
        if (!$user) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode(['count' => 0]));
        }

        $count = $this->Notifications->find()
            ->where([
                'user_id' => $user->id,
                'is_read' => false
            ])
            ->count();

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['count' => $count]));
    }

    /**
     * Mark a single notification as read
     *
     * @param string|null $id Notification id.
     * @return \Cake\Http\Response|null Redirects
     */
    public function markAsRead($id = null)
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $user = $this->Authentication->getIdentity();
        
        if (!$user) {
            $this->Flash->error('Unauthorized');
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $notification = $this->Notifications->get($id);
        
        // Ensure user owns this notification
        if ($notification->user_id !== $user->id) {
            $this->Flash->error('You cannot access this notification');
            return $this->redirect(['action' => 'index']);
        }

        $notification->is_read = true;
        $notification->read_at = new \DateTime();
        
        if ($this->Notifications->save($notification)) {
            $this->Flash->success('Notification marked as read');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Mark all notifications as read
     *
     * @return \Cake\Http\Response|null Redirects
     */
    public function markAllAsRead()
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $user = $this->Authentication->getIdentity();
        
        if (!$user) {
            $this->Flash->error('Unauthorized');
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $updated = $this->Notifications->updateAll(
            ['is_read' => true, 'read_at' => new \DateTime()],
            ['user_id' => $user->id, 'is_read' => false]
        );

        $this->Flash->success(__('Marked {0} notifications as read', $updated));
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
