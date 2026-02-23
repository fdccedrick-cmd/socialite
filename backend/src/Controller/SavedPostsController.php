<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * SavedPosts Controller
 * Handles saving and unsaving posts
 */
class SavedPostsController extends AppController
{
    /**
     * Save a post
     *
     * @return \Cake\Http\Response|null|void
     */
    public function save()
    {
        $this->request->allowMethod(['post']);
        
        $result = $this->Authentication->getResult();
        if (!$result || !$result->isValid()) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Not authenticated'
                ]));
        }

        $identity = $this->Authentication->getIdentity();
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
        }

        if (!$userId) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'User ID not found'
                ]));
        }

        $postId = $this->request->getData('post_id');
        
        if (!$postId) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Post ID is required'
                ]));
        }

        // Check if post exists and is not deleted
        $postsTable = $this->getTableLocator()->get('Posts');
        $post = $postsTable->find()
            ->where([
                'id' => $postId,
                'deleted IS' => null
            ])
            ->first();

        if (!$post) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Post not found'
                ]));
        }

        $savedPostsTable = $this->getTableLocator()->get('SavedPosts');
        
        // Check if already saved
        if ($savedPostsTable->isSaved($userId, $postId)) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Post already saved'
                ]));
        }

        // Save the post
        $savedPost = $savedPostsTable->newEntity([
            'user_id' => $userId,
            'post_id' => $postId,
        ]);

        if ($savedPostsTable->save($savedPost)) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Post saved successfully',
                    'is_saved' => true
                ]));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'message' => 'Failed to save post'
            ]));
    }

    /**
     * Unsave a post
     *
     * @return \Cake\Http\Response|null|void
     */
    public function unsave()
    {
        $this->request->allowMethod(['post']);
        
        $result = $this->Authentication->getResult();
        if (!$result || !$result->isValid()) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Not authenticated'
                ]));
        }

        $identity = $this->Authentication->getIdentity();
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
        }

        if (!$userId) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'User ID not found'
                ]));
        }

        $postId = $this->request->getData('post_id');
        
        if (!$postId) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Post ID is required'
                ]));
        }

        $savedPostsTable = $this->getTableLocator()->get('SavedPosts');
        
        // Find and delete the saved post
        $savedPost = $savedPostsTable->find()
            ->where([
                'SavedPosts.user_id' => $userId,
                'SavedPosts.post_id' => $postId
            ])
            ->first();

        if (!$savedPost) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Post was not saved'
                ]));
        }

        if ($savedPostsTable->delete($savedPost)) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Post unsaved successfully',
                    'is_saved' => false
                ]));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'message' => 'Failed to unsave post'
            ]));
    }
}
