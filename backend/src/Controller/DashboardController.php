<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Log\Log;

class DashboardController extends AppController
{
    public function index()
    {
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->redirect('/login');
        }

        $identity = $this->Authentication->getIdentity();
        
        // Get user ID from identity
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
 
        $usersTable = $this->getTableLocator()->get('Users');
        $userEntity = $usersTable->get($userId);
        $user = $userEntity->toArray();

        foreach (['created', 'modified'] as $dtField) {
            if (!empty($user[$dtField]) && $user[$dtField] instanceof \DateTimeInterface) {
                $user[$dtField] = $user[$dtField]->format(DATE_ATOM);
            }
        }
    
        $postsTable = $this->getTableLocator()->get('Posts');
        $likesTable = $this->getTableLocator()->get('Likes');
        $commentsTable = $this->getTableLocator()->get('Comments');
        
        $posts = $postsTable->find()
            ->where(['Posts.deleted IS' => null])
            ->contain(['Users', 'PostImages' => ['sort' => ['PostImages.sort_order' => 'ASC']]])
            ->order(['Posts.created' => 'DESC'])
            ->toArray();
        
        $postsArray = [];
        foreach ($posts as $post) {
            $postData = $post->toArray();
            if (!empty($postData['created']) && $postData['created'] instanceof \DateTimeInterface) {
                $postData['created'] = $postData['created']->format(DATE_ATOM);
            }
            if (!empty($postData['modified']) && $postData['modified'] instanceof \DateTimeInterface) {
                $postData['modified'] = $postData['modified']->format(DATE_ATOM);
            }
            
            // Post likes
            $postData['like_count'] = $likesTable->find()
                ->where(['target_type' => 'Post', 'target_id' => $post->id])
                ->count();
            
            $postData['is_liked'] = $likesTable->find()
                ->where([
                    'target_type' => 'Post',
                    'target_id' => $post->id,
                    'user_id' => $userId
                ])
                ->count() > 0;
            
            // Fetch comments with user data
            $comments = $commentsTable->find()
                ->where([
                    'post_id' => $post->id,
                    'deleted_at IS' => null
                ])
                ->contain(['Users'])
                ->order(['Comments.created_at' => 'ASC'])
                ->toArray();
            
            $postData['comment_count'] = count($comments);
            
            // Add like data to each comment
            $commentsArray = [];
            foreach ($comments as $comment) {
                $commentData = $comment->toArray();
                
                // Format comment dates
                if (!empty($commentData['created_at']) && $commentData['created_at'] instanceof \DateTimeInterface) {
                    $commentData['created_at'] = $commentData['created_at']->format(DATE_ATOM);
                }
                
                // Comment like count
                $commentData['like_count'] = $likesTable->find()
                    ->where([
                        'target_type' => 'Comment',
                        'target_id' => $comment->id
                    ])
                    ->count();
                
                // Check if current user liked this comment
                $commentData['is_liked'] = $likesTable->find()
                    ->where([
                        'target_type' => 'Comment',
                        'target_id' => $comment->id,
                        'user_id' => $userId
                    ])
                    ->count() > 0;
                
                $commentsArray[] = $commentData;
            }
            
            $postData['comments'] = $commentsArray;
            $postsArray[] = $postData;
        }

        $this->set(compact('user', 'postsArray'));
    }
}
