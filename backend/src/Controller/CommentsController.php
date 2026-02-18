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
            
            $user = $this->Authentication->getIdentity();
            if (!$user) {
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

            // Debug: Log incoming data
            error_log('Comment add - Raw POST data: ' . json_encode($this->request->getData()));

            // Optional: comment on a specific post image (for posts with 2+ images)
            if (isset($data['post_image_id']) && $data['post_image_id'] !== '' && $data['post_image_id'] !== null) {
                $data['post_image_id'] = (int)$data['post_image_id'];
                error_log('Comment add - Set post_image_id to: ' . $data['post_image_id']);
            } else {
                $data['post_image_id'] = null;
                error_log('Comment add - Set post_image_id to NULL');
            }
            
            // image upload
            if (!empty($data['content_image'])) {
                $image = $data['content_image'];
                if (is_object($image) && method_exists($image, 'getError') && $image->getError() === UPLOAD_ERR_OK) {
                    $filename = uniqid() . '_' . $image->getClientFilename();
                    $targetPath = WWW_ROOT . 'img' . DS . 'comment_uploads' . DS . $filename;
                    
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
            
            // Explicitly set post_image_id if it exists (workaround for CakePHP stripping it)
            if (isset($data['post_image_id']) && $data['post_image_id'] !== null) {
                $comment->set('post_image_id', $data['post_image_id']);
                $comment->setDirty('post_image_id', true);
            }
            
            // Debug: Log entity state before save
            error_log('Comment add - Data array being patched: ' . json_encode($data));
            error_log('Comment add - Entity after patch: ' . json_encode($comment->toArray()));
            error_log('Comment add - Entity post_image_id specifically: ' . var_export($comment->post_image_id, true));
            error_log('Comment add - Entity dirty fields: ' . json_encode($comment->getDirty()));
            error_log('Comment add - Validation errors before save: ' . json_encode($comment->getErrors()));
            
            // Try using raw SQL as a last resort
            if (isset($data['post_image_id']) && $data['post_image_id'] !== null) {
                error_log('Comment add - Using raw INSERT for image comment');
                
                try {
                    $connection = $this->Comments->getConnection();
                    
                    $insertData = [
                        'post_id' => (int)$data['post_id'],
                        'user_id' => (int)$data['user_id'],
                        'content_text' => $data['content_text'] ?? null,
                        'content_image_path' => $data['content_image_path'] ?? null,
                        'post_image_id' => (int)$data['post_image_id'],
                        'created_at' => (new \DateTime())->format('Y-m-d H:i:s')
                    ];
                    
                    error_log('Comment add - INSERT data: ' . json_encode($insertData));
                    
                    $statement = $connection->execute(
                        'INSERT INTO comments (post_id, user_id, content_text, content_image_path, post_image_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                        array_values($insertData)
                    );
                    
                    $rowCount = $statement->rowCount();
                    if ($rowCount > 0) {
                        $insertedId = (int)$connection->getDriver()->lastInsertId();
                        error_log('Comment add - INSERT succeeded with ID: ' . $insertedId);
                        
                        // Set all fields on the entity so it can be used later
                        $comment->id = $insertedId;
                        $comment->post_id = (int)$data['post_id'];
                        $comment->user_id = (int)$data['user_id'];
                        $comment->content_text = $data['content_text'] ?? null;
                        $comment->content_image_path = $data['content_image_path'] ?? null;
                        $comment->post_image_id = (int)$data['post_image_id'];
                        $comment->created_at = $data['created_at']; // Already a DateTime object
                        
                        $saveResult = $comment;
                    } else {
                        error_log('Comment add - INSERT failed: No rows affected');
                        $saveResult = false;
                    }
                } catch (\Exception $insertException) {
                    error_log('Comment add - INSERT exception: ' . $insertException->getMessage());
                    error_log('Comment add - INSERT trace: ' . $insertException->getTraceAsString());
                    $saveResult = false;
                }
            } else {
                $saveResult = $this->Comments->save($comment, ['checkRules' => false]);
            }
            
            error_log('Comment add - save() result type: ' . gettype($saveResult));
            error_log('Comment add - Validation errors after save: ' . json_encode($comment->getErrors()));
            
            if ($saveResult) {
                error_log('Comment add - Save succeeded');
                
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
                
                if ($this->request->is('ajax') || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                    error_log('Comment add - Preparing AJAX response');
                    
                    // Build response from the entity data directly
                    $commentArray = [
                        'id' => $comment->id,
                        'post_id' => $comment->post_id,
                        'post_image_id' => $comment->post_image_id ?? null,
                        'user_id' => $comment->user_id,
                        'content_text' => $comment->content_text,
                        'content_image_path' => $comment->content_image_path ?? null,
                        'created_at' => $comment->created_at instanceof \DateTimeInterface
                            ? $comment->created_at->format('c') : (string)$comment->created_at,
                        'user' => [
                            'id' => $user->id,
                            'full_name' => $user->full_name,
                            'username' => $user->username,
                            'profile_photo_path' => $user->profile_photo_path ?? null
                        ]
                    ];
                    
                    error_log('Comment add - Returning success response');
                    
                    return $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode([
                            'success' => true,
                            'message' => 'Comment posted successfully',
                            'comment' => $commentArray
                        ]));
                }
                
                $this->Flash->success(__('Your comment has been posted.'));
               
                return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
            } else {
                $errors = $comment->getErrors();
                error_log('Comment save failed: ' . json_encode($errors));
                
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
        
        $user = $this->Authentication->getIdentity();
        if ($comment->user_id !== $user->id) {
            $this->Flash->error(__('You can only edit your own comments.'));
            return $this->redirect($this->referer());
        }
        
        $data = $this->request->getData();
        $data['updated_at'] = new \DateTime();
        
        if (!empty($data['content_image'])) {
            $image = $data['content_image'];
            if ($image->getError() === UPLOAD_ERR_OK) {
                if (!empty($comment->content_image_path)) {
                    $oldPath = WWW_ROOT . $comment->content_image_path;
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                $filename = uniqid() . '_' . $image->getClientFilename();
                $targetPath = WWW_ROOT . 'img' . DS . 'comments' . DS . $filename;
                
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
        
        $user = $this->Authentication->getIdentity();
        $allowDelete = false;
        if ($user) {
            // Comment owner may delete
            if ($comment->user_id === $user->id) {
                $allowDelete = true;
            } else {
                // Post owner may also delete comments on their post
                $post = $this->fetchTable('Posts')->get($comment->post_id);
                if ($post && $post->user_id === $user->id) {
                    $allowDelete = true;
                }
            }
        }

        if (!$allowDelete) {
            $this->Flash->error(__('You can only delete your own comments.'));
            return $this->redirect($this->referer());
        }
        
        if ($this->Comments->softDelete((int)$id)) {
            if ($this->request->is('ajax') || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode(['success' => true]));
            }

            $this->Flash->success(__('Your comment has been deleted.'));
        } else {
            if ($this->request->is('ajax') || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(500)
                    ->withStringBody(json_encode(['success' => false, 'message' => 'Unable to delete comment']));
            }

            $this->Flash->error(__('Unable to delete your comment. Please try again.'));
        }
        
        return $this->redirect(['controller' => 'Posts', 'action' => 'view', $comment->post_id]);
    }

    /**
     * View method - redirect to the post page for a comment (deep link)
     *
     * @param string|null $id Comment id.
     * @return \
     */
    public function view($id = null)
    {
        $this->request->allowMethod(['get']);

        $comment = $this->Comments->get($id);
        if (!$comment) {
            throw new \Cake\Datasource\Exception\RecordNotFoundException('Comment not found');
        }

        // Redirect to the post view with an anchor to the comment
        $url = '/posts/' . $comment->post_id . '#comment-' . $comment->id;
        return $this->redirect($url);
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
            ->where([
                'post_id' => $postId,
                'post_image_id IS' => null,
            ])
            ->contain(['Users'])
            ->order(['Comments.created_at' => 'ASC'])
            ->all();

        $this->set('comments', $comments);
        $this->viewBuilder()->setOption('serialize', ['comments']);
    }

    /**
     * Get comments for a specific post image (AJAX)
     *
     * @param string|null $postImageId Post image id.
     * @return \Cake\Http\Response|null|void JSON response
     */
    public function getByPostImage($postImageId = null)
    {
        $this->request->allowMethod(['get']);
        $this->viewBuilder()->setClassName('Json');
        $postImageId = (int)$postImageId;

        $comments = $this->Comments->find('active')
            ->where(['post_image_id' => $postImageId])
            ->contain(['Users'])
            ->order(['Comments.created_at' => 'ASC'])
            ->all();

        $this->set('comments', $comments);
        $this->viewBuilder()->setOption('serialize', ['comments']);
    }
}
