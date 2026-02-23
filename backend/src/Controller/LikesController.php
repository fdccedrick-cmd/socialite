<?php
declare(strict_types=1);

namespace App\Controller;

use App\Utility\NotificationHelper;
use App\Utility\WebSocketClient;

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
        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->disableAutoLayout();
        $this->Likes = $this->fetchTable('Likes');
    }
    
    public function beforeRender(\Cake\Event\EventInterface $event)
    {
        // Don't call parent::beforeRender - skip the friends/suggestions logic for API endpoints
        // Just render the JSON response directly
        return;
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
                    
                   
                    try {
                        $postsTable = $this->fetchTable('Posts');
                        $post = $postsTable->find()
                            ->select(['id', 'user_id'])
                            ->where(['id' => $id])
                            ->first();
                        
                        if ($post && $post->user_id !== $userId) {
                            NotificationHelper::deleteLike(
                                (int)$post->user_id,
                                (int)$userId,
                                (int)$id,
                                null
                            );
                        }
                    } catch (\Exception $notifError) {
                        error_log('Delete notification error: ' . $notifError->getMessage());
                    }
                    
                 
                    try {
                        $ws = WebSocketClient::getInstance();
                        $ws->notifyUnlike('Post', (int)$id, (int)$userId);
                    } catch (\Exception $e) {
                        error_log('WebSocket unlike broadcast error: ' . $e->getMessage());
                      
                    }

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
                           
                                try {
                                    $ws = WebSocketClient::getInstance();
                                    $ws->notifyLike(
                                        'Post',
                                        (int)$id,
                                        (int)$userId,
                                        $user->full_name ?? $user->username,
                                        (int)$post->user_id
                                    );
                                } catch (\Exception $e) {
                                    error_log('WebSocket like broadcast error: ' . $e->getMessage());
                                 
                                }
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
                    
                   
                    try {
                        $commentsTable = $this->fetchTable('Comments');
                        $comment = $commentsTable->find()
                            ->select(['id', 'user_id'])
                            ->where(['id' => $id])
                            ->first();
                        
                        if ($comment && $comment->user_id !== $userId) {
                            NotificationHelper::deleteCommentLike(
                                (int)$comment->user_id,
                                (int)$userId,
                                (int)$id
                            );
                        }
                    } catch (\Exception $notifError) {
                        error_log('Delete comment like notification error: ' . $notifError->getMessage());
                    }

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
        error_log("========== togglePostImage START ==========");
        error_log("Image ID received: " . var_export($id, true));
        
        try {
            $this->request->allowMethod(['post']);
            error_log("Method check passed");
        } catch (\Exception $e) {
            error_log("Method check failed: " . $e->getMessage());
            return $this->response
                ->withType('application/json')
                ->withStatus(405)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Method not allowed']));
        }
        
        $identity = $this->Authentication->getIdentity();
        error_log("Identity retrieved: " . var_export($identity !== null, true));
        
        if (!$identity) {
            error_log("ERROR: No identity");
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
        
        error_log("User ID extracted: " . var_export($userId, true));

        if (!$userId) {
            error_log("ERROR: No user ID");
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => 'User ID not found']));
        }

        try {
            $id = (int)$id;
            error_log("Processing image like - imageId: $id, userId: $userId");
            
         
            error_log("Fetching PostImages table...");
            $postImagesTable = $this->fetchTable('PostImages');
            
            error_log("Querying for post image with id: $id");
            $postImage = $postImagesTable->find()
                ->select(['post_id'])
                ->where(['id' => $id])
                ->first();
            
            error_log("Post image query result: " . var_export($postImage !== null, true));
                
            if (!$postImage) {
                error_log("ERROR: Post image not found");
                return $this->response
                    ->withType('application/json')
                    ->withStatus(404)
                    ->withStringBody(json_encode(['success' => false, 'message' => 'Post image not found']));
            }
            
            error_log("Post image found - post_id: " . $postImage->post_id);
            
            error_log("Checking for existing like...");
            $existingLike = $this->Likes->find()
                ->where([
                    'user_id' => $userId,
                    'target_type' => 'Post',
                    'target_id' => $postImage->post_id,
                    'post_image_id' => $id
                ])
                ->first();
            
            error_log("Existing like found: " . var_export($existingLike !== null, true));
                
            if ($existingLike) {
                error_log("Deleting existing like...");
                if ($this->Likes->delete($existingLike)) {
                    error_log("Like deleted successfully");
                    $likeCount = $this->Likes->find()
                        ->where([
                            'target_type' => 'Post',
                            'target_id' => $postImage->post_id,
                            'post_image_id' => $id
                        ])
                        ->count();
                    
                    error_log("New like count: $likeCount");
                    
                   
                    try {
                        $postsTable = $this->fetchTable('Posts');
                        $post = $postsTable->find()
                            ->select(['id', 'user_id'])
                            ->where(['id' => $postImage->post_id])
                            ->first();
                        
                        if ($post && $post->user_id !== $userId) {
                            NotificationHelper::deleteLike(
                                (int)$post->user_id,
                                (int)$userId,
                                (int)$postImage->post_id,
                                (int)$id
                            );
                        }
                    } catch (\Exception $notifError) {
                        error_log('Delete post image notification error: ' . $notifError->getMessage());
                    }
                    
                    try {
                        $ws = WebSocketClient::getInstance();
                        $ws->broadcast([
                            'type' => 'like_removed',
                            'target_type' => 'PostImage',
                            'target_id' => $postImage->post_id,
                            'post_image_id' => (int)$id,
                            'user_id' => (int)$userId,
                            'timestamp' => time()
                        ]);
                    } catch (\Exception $e) {
                        error_log('WebSocket image unlike broadcast error: ' . $e->getMessage());
                    }
                    
                    error_log("Returning unlike success response");
                    return $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode([
                            'success' => true,
                            'liked' => false,
                            'likeCount' => $likeCount
                        ]));
                }
            } else {
                error_log("Creating new like...");
                $like = $this->Likes->newEntity([
                    'user_id' => $userId,
                    'target_type' => 'Post',
                    'target_id' => $postImage->post_id,
                    'post_image_id' => $id
                ]);
                
                error_log("Like entity created: " . json_encode($like->toArray()));

                if ($this->Likes->save($like)) {
                    error_log("Like saved successfully");
                    $likeCount = $this->Likes->find()
                        ->where([
                            'target_type' => 'Post',
                            'target_id' => $postImage->post_id,
                            'post_image_id' => $id
                        ])
                        ->count();

                    error_log("New like count: $likeCount");

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
                            ->where(['id' => $postImage->post_id])
                            ->first();

                        if ($post && $post->user_id !== $userId) {
                            $usersTable = $this->fetchTable('Users');
                            $user = $usersTable->find()
                                ->select(['id', 'username', 'full_name'])
                                ->where(['id' => $userId])
                                ->first();
                            
                            if ($user) {
                                NotificationHelper::likePostImage(
                                    (int)$post->user_id,
                                    (int)$userId,
                                    (int)$postImage->post_id,
                                    (int)$id,
                                    (string)($user->full_name ?? $user->username)
                                );
                                
                                
                                try {
                                    $ws = WebSocketClient::getInstance();
                                    $ws->notifyLike(
                                        'Post',
                                        (int)$postImage->post_id,
                                        (int)$userId,
                                        $user->full_name ?? $user->username,
                                        (int)$post->user_id
                                    );
                                } catch (\Exception $e) {
                                    error_log('WebSocket like notification error: ' . $e->getMessage());
                                }
                            }
                        }
                    } catch (\Exception $notifError) {
                        error_log('Notification error: ' . $notifError->getMessage());
                    }

                 
                    try {
                        $ws = WebSocketClient::getInstance();
                        $ws->broadcast([
                            'type' => 'like_added',
                            'target_type' => 'PostImage',
                            'target_id' => $postImage->post_id,
                            'post_image_id' => (int)$id,
                            'user_id' => (int)$userId,
                            'timestamp' => time()
                        ]);
                    } catch (\Exception $e) {
                        error_log('WebSocket image like broadcast error: ' . $e->getMessage());
                    }
                    
                    error_log("Returning like success response");
                    return $response;
                } else {
                    $errors = $like->getErrors();
                    error_log("ERROR: Failed to save like - " . json_encode($errors));
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

            error_log("ERROR: Reached end without return");
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Failed to toggle like']));
        } catch (\Exception $e) {
            error_log("EXCEPTION in togglePostImage: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
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
