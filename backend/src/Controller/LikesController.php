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
            $existingLike = $this->Likes->find()
                ->where([
                    'user_id' => $userId,
                    'target_type' => 'Post',
                    'target_id' => $id,
                    'post_image_id IS' => null
                ])
                ->first();

            if ($existingLike) {
                if ($this->Likes->delete($existingLike)) {
                    $likeCount = $this->Likes->find()
                        ->where([
                            'target_type' => 'Post',
                            'target_id' => $id,
                            'post_image_id IS' => null
                        ])
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
                $like = $this->Likes->newEntity([
                    'user_id' => $userId,
                    'target_type' => 'Post',
                    'target_id' => $id,
                    'post_image_id' => null
                ]);

                if ($this->Likes->save($like)) {
                    $likeCount = $this->Likes->find()
                        ->where([
                            'target_type' => 'Post',
                            'target_id' => $id,
                            'post_image_id IS' => null
                        ])
                        ->count();

                    $response = $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode([
                            'success' => true,
                            'liked' => true,
                            'likeCount' => $likeCount
                        ]));

    
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

            
            $existingLike = $this->Likes->find()
                ->where([
                    'user_id' => $userId,
                    'target_type' => 'Comment',
                    'target_id' => $id
                ])
                ->first();

            if ($existingLike) {
                
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

                   
                    try {
                        $commentsTable = $this->fetchTable('Comments');
                        $comment = $commentsTable->find()
                            ->select(['id', 'user_id', 'post_id'])
                            ->where(['id' => $id])
                            ->first();

                        if ($comment && $comment->user_id !== $userId) {
                            $usersTable = $this->fetchTable('Users');
                            $user = $usersTable->find()
                                ->select(['id', 'username', 'full_name'])
                                ->where(['id' => $userId])
                                ->first();
                            
                            if ($user) {
                                NotificationHelper::commentLike(
                                    (int)$comment->user_id,
                                    (int)$userId,
                                    (int)$comment->post_id,
                                    (int)$id,
                                    (string)($user->full_name ?? $user->username)
                                );
                            }
                        }
                    } catch (\Exception $notifError) {
                        error_log('Comment like notification error: ' . $notifError->getMessage());
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
            ->where([
                'target_type' => 'Post',
                'target_id' => $id,
                'post_image_id IS' => null
            ])
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

    /**
     * Toggle like on a post image
     *
     * @param int|null $id Post image ID
     * @return \Cake\Http\Response|null JSON response
     */
    public function togglePostImage($id = null)
    {
        $this->request->allowMethod(['post']);
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Unauthorized']));
        }
        $userId = $identity->id ?? ($identity['id'] ?? null);
        if (!$userId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => 'User ID not found']));
        }
        $id = (int)$id;
        
        error_log("togglePostImage - image_id: $id, user_id: $userId");
        
        try {
            // First, get the post_id from post_images table
            $postImagesTable = $this->fetchTable('PostImages');
            $postImage = $postImagesTable->find()
                ->select(['post_id'])
                ->where(['id' => $id])
                ->first();
                
            if (!$postImage) {
                error_log("togglePostImage - image not found: $id");
                return $this->response
                    ->withType('application/json')
                    ->withStatus(404)
                    ->withStringBody(json_encode(['success' => false, 'message' => 'Post image not found']));
            }
            
            error_log("togglePostImage - found post_id: " . $postImage->post_id);
            
            $existingLike = $this->Likes->find()
                ->where([
                    'user_id' => $userId,
                    'target_type' => 'Post',
                    'target_id' => $postImage->post_id,
                    'post_image_id' => $id
                ])
                ->first();
                
            if ($existingLike) {
                error_log("togglePostImage - deleting existing like");
                $this->Likes->delete($existingLike);
                $likeCount = $this->Likes->find()
                    ->where([
                        'target_type' => 'Post',
                        'target_id' => $postImage->post_id,
                        'post_image_id' => $id
                    ])
                    ->count();
                error_log("togglePostImage - deleted, new count: $likeCount");
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'liked' => false,
                        'likeCount' => $likeCount
                    ]));
            }
            
            error_log("togglePostImage - creating new like");
            $like = $this->Likes->newEntity([
                'user_id' => $userId,
                'target_type' => 'Post',
                'target_id' => $postImage->post_id,
                'post_image_id' => $id
            ]);
            
            error_log("togglePostImage - like entity: " . json_encode($like->toArray()));
            
            if ($this->Likes->save($like)) {
                $likeCount = $this->Likes->find()
                    ->where([
                        'target_type' => 'Post',
                        'target_id' => $postImage->post_id,
                        'post_image_id' => $id
                    ])
                    ->count();
                error_log("togglePostImage - saved, new count: $likeCount");
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'liked' => true,
                        'likeCount' => $likeCount
                    ]));
            }
            
            $errors = $like->getErrors();
            error_log("togglePostImage - save failed: " . json_encode($errors));
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode([
                    'success' => false, 
                    'message' => 'Failed to save like',
                    'errors' => $errors
                ]));
        } catch (\Exception $e) {
            error_log("togglePostImage - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
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
     * Get like info for a post image
     *
     * @param int|null $id Post image ID
     * @return \Cake\Http\Response|null JSON response
     */
    public function getPostImageLikes($id = null)
    {
        $this->request->allowMethod(['get']);
        $id = (int)$id;
        
        // Get the post_id from post_images table
        $postImagesTable = $this->fetchTable('PostImages');
        $postImage = $postImagesTable->find()
            ->select(['post_id'])
            ->where(['id' => $id])
            ->first();
            
        if (!$postImage) {
            return $this->response
                ->withType('application/json')
                ->withStatus(404)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Post image not found',
                    'count' => 0,
                    'is_liked' => false
                ]));
        }
        
        $likeCount = $this->Likes->find()
            ->where([
                'target_type' => 'Post',
                'target_id' => $postImage->post_id,
                'post_image_id' => $id
            ])
            ->count();
        $isLiked = false;
        $identity = $this->Authentication->getIdentity();
        if ($identity) {
            $userId = $identity->id ?? ($identity['id'] ?? null);
            if ($userId) {
                $isLiked = $this->Likes->find()
                    ->where([
                        'target_type' => 'Post',
                        'target_id' => $postImage->post_id,
                        'post_image_id' => $id,
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
