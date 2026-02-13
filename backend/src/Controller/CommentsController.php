<?php
declare(strict_types=1);

namespace App\Controller;

use App\Utility\NotificationHelper;

/**
 * Comments Controller
 *
 * @property \App\Model\Table\CommentsTable $Comments
 */
class CommentsController extends AppController
{
    /**
     * Add method - Create a new comment on a post
     *
     * @return \Cake\Http\Response|null|void JSON response for AJAX or redirect
     */
    public function add()
    {
        $this->request->allowMethod(['post']);
        
        try {
            $comment = $this->Comments->newEmptyEntity();
            $data = $this->request->getData();
            
            // Get the logged-in user
            $user = $this->Authentication->getIdentity();
            if (!$user) {
                // Check if it's an AJAX request
                if ($this->request->is('ajax') || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                    return $this->response
                        ->withType('application/json')
                        ->withStatus(401)
                        ->withStringBody(json_encode([
                            'success' => false,
                            'message' => 'You must be logged in to comment.'
                        ]));
                }
                
                $this->Flash->error(__('You must be logged in to comment.'));
                return $this->redirect(['controller' => 'Users', 'action' => 'login']);
            }
            
            $data['user_id'] = $user->id;
            $data['created_at'] = new \DateTime();
            
            // Handle image upload if present
            if (!empty($data['content_image'])) {
                $image = $data['content_image'];
                if (is_object($image) && method_exists($image, 'getError') && $image->getError() === UPLOAD_ERR_OK) {
                    $filename = uniqid() . '_' . $image->getClientFilename();
                    $targetPath = WWW_ROOT . 'img' . DS . 'comment_uploads' . DS . $filename;
                    
                    // Create directory if it doesn't exist
                    $dir = WWW_ROOT . 'img' . DS . 'comment_uploads';
                    if (!file_exists($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    
                    $image->moveTo($targetPath);
                    $data['content_image_path'] = 'img/comment_uploads/' . $filename;
                }
                unset($data['content_image']);
            }
            
            $comment = $this->Comments->patchEntity($comment, $data);
            
            if ($this->Comments->save($comment)) {
                // Send notification to post owner
                try {
                    $postsTable = $this->fetchTable('Posts');
                    $post = $postsTable->find()
                        ->select(['user_id'])
                        ->where(['id' => $data['post_id']])
                        ->first();
                    
                    if ($post && $post->user_id !== $user->id) {
                        NotificationHelper::comment(
                            (int)$post->user_id,
                            (int)$user->id,
                            (int)$data['post_id'],
                            (string)$user->full_name
                        );
                    }
                } catch (\Exception $e) {
                    error_log('Comment notification error: ' . $e->getMessage());
                }
                
                // Return JSON for AJAX requests
                if ($this->request->is('ajax') || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                    // Load the comment with user data
                    $savedComment = $this->Comments->get($comment->id, [
                        'contain' => ['Users']
                    ]);
                    
                    return $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode([
                            'success' => true,
                            'message' => 'Comment posted successfully',
                            'comment' => $savedComment
                        ]));
                }
                
                $this->Flash->success(__('Your comment has been posted.'));
                
                // Redirect back to dashboard (for non-AJAX)
                return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
            } else {
                $errors = $comment->getErrors();
                error_log('Comment save failed: ' . json_encode($errors));
                
                // Return JSON error for AJAX
                if ($this->request->is('ajax') || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                    return $this->response
                        ->withType('application/json')
                        ->withStatus(400)
                        ->withStringBody(json_encode([
                            'success' => false,
                            'message' => 'Unable to post your comment',
                            'errors' => $errors
                        ]));
                }
                
                $this->Flash->error(__('Unable to post your comment. Please try again.'));
                return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
            }
        } catch (\Exception $e) {
            error_log('Comment add error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            // Return JSON error for AJAX
            if ($this->request->is('ajax') || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(500)
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => 'Server error: ' . $e->getMessage()
                    ]));
            }
            
            $this->Flash->error(__('An error occurred. Please try again.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
        }
    }

    /**
     * Edit method - Edit a comment
     *
     * @param string|null $id Comment id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->request->allowMethod(['put', 'post', 'patch']);
        
        $comment = $this->Comments->get($id, [
            'contain' => [],
        ]);
        
        // Check if user owns this comment
        $user = $this->Authentication->getIdentity();
        if ($comment->user_id !== $user->id) {
            $this->Flash->error(__('You can only edit your own comments.'));
            return $this->redirect($this->referer());
        }
        
        $data = $this->request->getData();
        $data['updated_at'] = new \DateTime();
        
        // Handle image upload if present
        if (!empty($data['content_image'])) {
            $image = $data['content_image'];
            if ($image->getError() === UPLOAD_ERR_OK) {
                // Delete old image if exists
                if (!empty($comment->content_image_path)) {
                    $oldPath = WWW_ROOT . $comment->content_image_path;
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                $filename = uniqid() . '_' . $image->getClientFilename();
                $targetPath = WWW_ROOT . 'img' . DS . 'comments' . DS . $filename;
                
                // Create directory if it doesn't exist
                $dir = WWW_ROOT . 'img' . DS . 'comments';
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                $image->moveTo($targetPath);
                $data['content_image_path'] = 'img/comments/' . $filename;
            }
            unset($data['content_image']);
        }
        
        $comment = $this->Comments->patchEntity($comment, $data);
        
        if ($this->Comments->save($comment)) {
            $this->Flash->success(__('Your comment has been updated.'));
        } else {
            $this->Flash->error(__('Unable to update your comment. Please try again.'));
        }
        
        return $this->redirect(['controller' => 'Posts', 'action' => 'view', $comment->post_id]);
    }

    /**
     * Delete method - Soft delete a comment
     *
     * @param string|null $id Comment id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        
        $comment = $this->Comments->get($id);
        
        // Check if user owns this comment
        $user = $this->Authentication->getIdentity();
        if ($comment->user_id !== $user->id) {
            $this->Flash->error(__('You can only delete your own comments.'));
            return $this->redirect($this->referer());
        }
        
        if ($this->Comments->softDelete((int)$id)) {
            $this->Flash->success(__('Your comment has been deleted.'));
        } else {
            $this->Flash->error(__('Unable to delete your comment. Please try again.'));
        }
        
        return $this->redirect(['controller' => 'Posts', 'action' => 'view', $comment->post_id]);
    }

    /**
     * Get comments for a specific post (AJAX)
     *
     * @param string|null $postId Post id.
     * @return \Cake\Http\Response|null|void JSON response
     */
    public function getByPost($postId = null)
    {
        $this->request->allowMethod(['get']);
        $this->viewBuilder()->setClassName('Json');
        
        $comments = $this->Comments->find('active')
            ->where(['post_id' => $postId])
            ->contain(['Users'])
            ->order(['Comments.created_at' => 'ASC'])
            ->all();
        
        $this->set('comments', $comments);
        $this->viewBuilder()->setOption('serialize', ['comments']);
    }
}
