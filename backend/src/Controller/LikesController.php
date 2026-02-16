<?php
declare(strict_types=1);

namespace App\Controller;

use App\Utility\NotificationHelper;

/**
 * Likes Controller
 *
 * @property \App\Model\Table\LikesTable $Likes
 */
class LikesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
        
        // Disable auto-render for this controller (API only)
        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->disableAutoLayout();
        
        // Load the Likes table
        $this->Likes = $this->fetchTable('Likes');
    }

    /**
     * Toggle like on a post
     *
     * @param int|null $id Post ID
     * @return \Cake\Http\Response|null JSON response
     */
    public function togglePost($id = null)
    {
        $this->request->allowMethod(['post']);
        $identity = $this->Authentication->getIdentity();
        
        if (!$identity) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Unauthorized']));
        }

        // Extract user ID from identity
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

        if (!$userId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => 'User ID not found']));
        }

        try {
            // Check if already liked
            $existingLike = $this->Likes->find()
                ->where([
                    'user_id' => $userId,
                    'target_type' => 'Post',
                    'target_id' => $id
                ])
                ->first();

            if ($existingLike) {
                // Unlike - delete the like
                if ($this->Likes->delete($existingLike)) {
                    // Get new like count
                    $likeCount = $this->Likes->find()
                        ->where(['target_type' => 'Post', 'target_id' => $id])
                        ->count();

                    return $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode([
                            'success' => true,
                            'liked' => false,
                            'likeCount' => $likeCount
                        ]));
                } else {
                    return $this->response
                        ->withType('application/json')
                        ->withStatus(400)
                        ->withStringBody(json_encode([
                            'success' => false,
                            'message' => 'Failed to delete like'
                        ]));
                }
            } else {
                // Like - create new like
                $like = $this->Likes->newEntity([
                    'user_id' => $userId,
                    'target_type' => 'Post',
                    'target_id' => $id
                ]);

                if ($this->Likes->save($like)) {
                    // Get new like count
                    $likeCount = $this->Likes->find()
                        ->where(['target_type' => 'Post', 'target_id' => $id])
                        ->count();

                    // Return success immediately - notification creation moved to background
                    $response = $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode([
                            'success' => true,
                            'liked' => true,
                            'likeCount' => $likeCount
                        ]));

                    // Create notification asynchronously (don't let it break the like action)
                    try {
                        $postsTable = $this->fetchTable('Posts');
                        $post = $postsTable->find()
                            ->select(['id', 'user_id'])
                            ->where(['id' => $id])
                            ->first();

                        if ($post && $post->user_id !== $userId) {
                            $usersTable = $this->fetchTable('Users');
                            $user = $usersTable->find()
                                ->select(['id', 'username', 'full_name'])
                                ->where(['id' => $userId])
                                ->first();
                            
                            if ($user) {
                                NotificationHelper::like(
                                    (int)$post->user_id,
                                    (int)$userId,
                                    (int)$id,
                                    (string)($user->full_name ?? $user->username)
                                );
                            }
                        }
                    } catch (\Exception $notifError) {
                        // Notification failed but like succeeded - just log it
                        error_log('Notification error: ' . $notifError->getMessage());
                    }

                    return $response;
                } else {
                    $errors = $like->getErrors();
                    return $this->response
                        ->withType('application/json')
                        ->withStatus(400)
                        ->withStringBody(json_encode([
                            'success' => false, 
                            'message' => 'Failed to save like',
                            'errors' => $errors
                        ]));
                }
            }

            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Failed to toggle like']));
        } catch (\Exception $e) {
            
            return $this->response
                ->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode([
                    'success' => false, 
                    'message' => 'Server error: ' . $e->getMessage()
                ]));
        }
    }

    /**
     * Toggle like on a comment
     *
     * @param int|null $id Comment ID
     * @return \Cake\Http\Response|null JSON response
     */
    public function toggleComment($id = null)
    {
        try {
            $this->request->allowMethod(['post']);
            $identity = $this->Authentication->getIdentity();
            
            if (!$identity) {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(401)
                    ->withStringBody(json_encode(['success' => false, 'message' => 'Unauthorized']));
            }

            // Extract user ID from identity
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

            if (!$userId) {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(401)
                    ->withStringBody(json_encode(['success' => false, 'message' => 'User ID not found']));
            }

            // Check if already liked
            $existingLike = $this->Likes->find()
                ->where([
                    'user_id' => $userId,
                    'target_type' => 'Comment',
                    'target_id' => $id
                ])
                ->first();

            if ($existingLike) {
                // Unlike
                if ($this->Likes->delete($existingLike)) {
                    $likeCount = $this->Likes->find()
                        ->where(['target_type' => 'Comment', 'target_id' => $id])
                        ->count();

                    return $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode([
                            'success' => true,
                            'liked' => false,
                            'likeCount' => $likeCount
                        ]));
                }
            } else {
                // Like
                $like = $this->Likes->newEntity([
                    'user_id' => $userId,
                    'target_type' => 'Comment',
                    'target_id' => $id
                ]);

                if ($this->Likes->save($like)) {
                    $likeCount = $this->Likes->find()
                        ->where(['target_type' => 'Comment', 'target_id' => $id])
                        ->count();

                    $response = $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode([
                            'success' => true,
                            'liked' => true,
                            'likeCount' => $likeCount
                        ]));

                    // Create notification for comment owner
                    try {
                        error_log('[DEBUG] Starting comment like notification creation for comment ID: ' . $id);
                        $commentsTable = $this->fetchTable('Comments');
                        $comment = $commentsTable->find()
                            ->select(['id', 'user_id', 'post_id'])
                            ->where(['id' => $id])
                            ->first();

                        error_log('[DEBUG] Comment found: ' . ($comment ? 'yes' : 'no'));
                        if ($comment) {
                            error_log('[DEBUG] Comment owner ID: ' . $comment->user_id . ', Liker ID: ' . $userId);
                        }

                        if ($comment && $comment->user_id !== $userId) {
                            error_log('[DEBUG] Different user, fetching liker details...');
                            $usersTable = $this->fetchTable('Users');
                            $user = $usersTable->find()
                                ->select(['id', 'username', 'full_name'])
                                ->where(['id' => $userId])
                                ->first();
                            
                            error_log('[DEBUG] User found: ' . ($user ? 'yes' : 'no'));
                            if ($user) {
                                error_log('[DEBUG] Calling NotificationHelper::commentLike()');
                                $result = NotificationHelper::commentLike(
                                    (int)$comment->user_id,
                                    (int)$userId,
                                    (int)$comment->post_id,
                                    (int)$id,
                                    (string)($user->full_name ?? $user->username)
                                );
                                error_log('[DEBUG] Notification creation result: ' . ($result ? 'success' : 'failed'));
                            }
                        } else {
                            error_log('[DEBUG] Skipping notification - same user or no comment');
                        }
                    } catch (\Exception $notifError) {
                        error_log('[ERROR] Comment like notification error: ' . $notifError->getMessage());
                        error_log('[ERROR] Stack trace: ' . $notifError->getTraceAsString());
                    }

                    return $response;
                }
            }

            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Failed to toggle like']));
                
        } catch (\Exception $e) {
            error_log('toggleComment error: ' . $e->getMessage());
            return $this->response
                ->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Server error: ' . $e->getMessage()
                ]));
        }
    }

    /**
     * Get likes for a post
     *
     * @param int|null $id Post ID
     * @return \Cake\Http\Response|null JSON response
     */
    public function getPostLikes($id = null)
    {
        $this->request->allowMethod(['get']);
        
        $likes = $this->Likes->find()
            ->where(['target_type' => 'Post', 'target_id' => $id])
            ->contain(['Users' => ['fields' => ['id', 'username', 'full_name', 'profile_photo_path']]])
            ->all();

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'likes' => $likes,
                'count' => $likes->count()
            ]));
    }

    /**
     * Get like info for a comment
     *
     * @param int|null $id Comment ID
     * @return \Cake\Http\Response|null JSON response
     */
    public function getCommentLikes($id = null)
    {
        $this->request->allowMethod(['get']);
        
        $likeCount = $this->Likes->find()
            ->where(['target_type' => 'Comment', 'target_id' => $id])
            ->count();

        $isLiked = false;
        $identity = $this->Authentication->getIdentity();
        if ($identity) {
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
            
            if ($userId) {
                $isLiked = $this->Likes->find()
                    ->where([
                        'target_type' => 'Comment',
                        'target_id' => $id,
                        'user_id' => $userId
                    ])
                    ->count() > 0;
            }
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'count' => $likeCount,
                'is_liked' => $isLiked
            ]));
    }
}
