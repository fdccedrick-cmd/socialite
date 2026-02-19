<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use App\Utility\WebSocketClient;
use App\Utility\ImageProcessor;

class PostsController extends AppController
{
    public function create()
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->disableAutoLayout();
    
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access'
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
        } elseif (is_array($identity) && isset($identity['id'])) {
            $userId = $identity['id'];
        }

        if (!$userId) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]));
        }

        $contentText = $this->request->getData('content_text');
        $privacy = $this->request->getData('privacy', 'public');
        $uploadedFiles = $this->request->getData('post_images');

        if (empty($contentText) && empty($uploadedFiles)) {
            $this->Flash->error('Post must have either text or images.');
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Post must have either text or images'
                ]));
        }

        $post = $this->Posts->newEmptyEntity();
        $postData = [
            'user_id' => $userId,
            'content_text' => $contentText,
            'privacy' => $privacy
        ];

        $post = $this->Posts->patchEntity($post, $postData);

        $connection = $this->Posts->getConnection();
        $connection->begin();

        try {
            if (!$this->Posts->save($post)) {
                $connection->rollback();
                $errors = $post->getErrors();
                Log::error('Failed to save post: ' . json_encode($errors));
                
                $this->Flash->error('Failed to create post. Please try again.');
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => 'Failed to create post',
                        'errors' => $errors
                    ]));
            }
            
            // Broadcast new post via WebSocket
            try {
                $usersTable = $this->getTableLocator()->get('Users');
                $user = $usersTable->get($userId);
                $userName = $user->full_name ?? $user->username ?? 'User';
                
                $ws = WebSocketClient::getInstance();
                $ws->notifyNewPost($post->id, $userId, $userName);
            } catch (\Exception $e) {
                Log::error('WebSocket broadcast failed: ' . $e->getMessage());
            }

            if (!empty($uploadedFiles)) {
                $postImagesTable = $this->getTableLocator()->get('PostImages');
            
                if (!is_array($uploadedFiles)) {
                    $uploadedFiles = [$uploadedFiles];
                }

                $sortOrder = 0;
                foreach ($uploadedFiles as $uploadedFile) {
                    if (is_object($uploadedFile) && method_exists($uploadedFile, 'getError') && $uploadedFile->getError() === UPLOAD_ERR_OK) {
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                        $fileType = $uploadedFile->getClientMediaType();
                        
                        if (!in_array($fileType, $allowedTypes)) {
                            $connection->rollback();
                            $this->Flash->error('Invalid file type. Only JPG, PNG, and GIF are allowed.');
                            return $this->response
                                ->withType('application/json')
                                ->withStringBody(json_encode([
                                    'success' => false,
                                    'message' => 'Invalid file type'
                                ]));
                        }

                        $maxSize = 50 * 1024 * 1024; // 50MB for high-res images
                        if ($uploadedFile->getSize() > $maxSize) {
                            $connection->rollback();
                            $this->Flash->error('File size must be less than 50MB per image.');
                            return $this->response
                                ->withType('application/json')
                                ->withStringBody(json_encode([
                                    'success' => false,
                                    'message' => 'File too large'
                                ]));
                        }
                        
                        // Check minimum dimensions (Facebook-style validation)
                        $tempPath = $uploadedFile->getStream()->getMetadata('uri');
                        $imageInfo = @getimagesize($tempPath);
                        if ($imageInfo !== false) {
                            [$width, $height] = $imageInfo;
                            $minWidth = 480;
                            $minHeight = 320;
                            
                            if ($width < $minWidth || $height < $minHeight) {
                                $connection->rollback();
                                $this->Flash->error("Image is too small. Minimum dimensions: {$minWidth}×{$minHeight}px. Your image: {$width}×{$height}px.");
                                return $this->response
                                    ->withType('application/json')
                                    ->withStringBody(json_encode([
                                        'success' => false,
                                        'message' => "Image too small: {$width}×{$height}px. Minimum: {$minWidth}×{$minHeight}px"
                                    ]));
                            }
                        }

                        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                        $filename = 'post_' . $post->id . '_' . time() . '_' . $sortOrder . '.' . $extension;
                        
                        
                        $uploadDir = WWW_ROOT . 'img' . DS . 'post_uploads';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $uploadPath = $uploadDir . DS . $filename;
                        
                        try {
                            // First, move uploaded file to temporary location
                            $tempPath = $uploadDir . DS . 'temp_' . $filename;
                            $uploadedFile->moveTo($tempPath);
                            
                            // Process image (resize, compress, sharpen)
                            if (ImageProcessor::isAvailable()) {
                                $processSuccess = ImageProcessor::processImage($tempPath, $uploadPath);
                                
                                if ($processSuccess) {
                                    // Remove temp file
                                    @unlink($tempPath);
                                    error_log("PostsController: Successfully processed image $filename");
                                } else {
                                    // If processing fails, use original
                                    @rename($tempPath, $uploadPath);
                                    error_log("PostsController: Image processing failed for $filename, using original");
                                }
                            } else {
                                // GD not available, use original
                                @rename($tempPath, $uploadPath);
                                error_log("PostsController: GD library not available, using original image");
                            }
                            
                            
                            $postImage = $postImagesTable->newEmptyEntity();
                            $postImage = $postImagesTable->patchEntity($postImage, [
                                'post_id' => $post->id,
                                'image_path' => '/img/post_uploads/' . $filename,
                                'sort_order' => $sortOrder
                            ]);
                            
                            if (!$postImagesTable->save($postImage)) {
                                throw new \Exception('Failed to save post image');
                            }
                            
                            $sortOrder++;
                        } catch (\Exception $e) {
                            $connection->rollback();
                            Log::error('Failed to upload post image: ' . $e->getMessage());
                            $this->Flash->error('Failed to upload images.');
                            return $this->response
                                ->withType('application/json')
                                ->withStringBody(json_encode([
                                    'success' => false,
                                    'message' => 'Failed to upload images'
                                ]));
                        }
                    }
                }
            }

            $connection->commit();
            
            $this->Flash->success('Post created successfully!');
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Post created successfully',
                    'post_id' => $post->id
                ]));

        } catch (\Exception $e) {
            $connection->rollback();
            Log::error('Error creating post: ' . $e->getMessage());
            $this->Flash->error('An error occurred. Please try again.');
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'An error occurred'
                ]));
        }
    }

    /**
     * View a single post
     */
    public function view($id = null)
    {
        try {
            $post = $this->Posts->get($id, [
                'contain' => ['Users', 'PostImages' => ['sort' => ['PostImages.sort_order' => 'ASC']]]
            ]);

            if (!empty($post->deleted)) {
                $this->render('not_found');
                return;
            }

            // Build a post array with engagement data matching DashboardService::getPostsWithEngagement
            $postArray = $post->toArray();
            if (!empty($postArray['created']) && $postArray['created'] instanceof \DateTimeInterface) {
                $postArray['created'] = $postArray['created']->format(DATE_ATOM);
            }
            if (!empty($postArray['modified']) && $postArray['modified'] instanceof \DateTimeInterface) {
                $postArray['modified'] = $postArray['modified']->format(DATE_ATOM);
            }

            $likesTable = $this->getTableLocator()->get('Likes');
            $commentsTable = $this->getTableLocator()->get('Comments');

            // Determine current user id if available
            $identity = $this->Authentication->getIdentity();
            $currentUserId = null;
            if (is_object($identity)) {
                if (method_exists($identity, 'getOriginalData')) {
                    $orig = $identity->getOriginalData();
                    if (is_object($orig) && isset($orig->id)) {
                        $currentUserId = $orig->id;
                    } elseif (is_array($orig) && isset($orig['id'])) {
                        $currentUserId = $orig['id'];
                    }
                } elseif (isset($identity->id)) {
                    $currentUserId = $identity->id;
                }
            } elseif (is_array($identity) && isset($identity['id'])) {
                $currentUserId = $identity['id'];
            }

            $postArray['like_count'] = $likesTable->getLikeCount('Post', $post->id);
            $postArray['is_liked'] = $currentUserId ? $likesTable->isLikedByUser('Post', $post->id, $currentUserId) : false;
            $postArray['comments'] = $commentsTable->getCommentsForPost($post->id, $currentUserId);
            $postArray['comment_count'] = count($postArray['comments']);

            $this->set(compact('post', 'postArray'));
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            // Post doesn't exist - show not found page
            Log::info('Post not found: ' . $id);
            $this->render('not_found');
            return;
        } catch (\Exception $e) {
            Log::error('Error loading post view: ' . $e->getMessage());
            // For other errors, also show not found page to avoid exposing system errors
            $this->render('not_found');
            return;
        }
    }

    /**
     * Return a rendered post element for embedding (no layout)
     * Example: GET /posts/get-any/1
     */
    public function getAnyPost($id = null)
    {
        try {
            $post = $this->Posts->get($id, [
                'contain' => ['Users', 'PostImages' => ['sort' => ['PostImages.sort_order' => 'ASC']]]
            ]);

            if (!empty($post->deleted)) {
                $this->viewBuilder()->disableAutoLayout();
                $this->render('not_found');
                return;
            }

            $this->viewBuilder()->disableAutoLayout();
            $this->set(compact('post'));
            // will render templates/Posts/get_any_post.php which uses the post_card element
            $this->render('get_any_post');
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            Log::info('Post not found in getAnyPost: ' . $id);
            $this->viewBuilder()->disableAutoLayout();
            $this->render('not_found');
            return;
        } catch (\Exception $e) {
            Log::error('Error rendering post element: ' . $e->getMessage());
            $this->viewBuilder()->disableAutoLayout();
            $this->render('not_found');
            return;
        }
    }

    /**
     * Edit/Update a post
     */
    public function edit($id = null)
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $this->viewBuilder()->disableAutoLayout();
        
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access'
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
        } elseif (is_array($identity) && isset($identity['id'])) {
            $userId = $identity['id'];
        }

        if (!$userId) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]));
        }

        try {
            $post = $this->Posts->get($id, [
                'contain' => ['PostImages']
            ]);
            
            if ($post->user_id !== $userId) {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(403)
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => 'You can only edit your own posts'
                    ]));
            }
            
            $contentText = $this->request->getData('content_text');
            $privacy = $this->request->getData('privacy');
            $removedImageIds = $this->request->getData('removed_images', []);
            $newImages = $this->request->getData('new_images');
            
            $remainingImagesCount = count($post->post_images) - count($removedImageIds);
            $newImagesCount = !empty($newImages) ? (is_array($newImages) ? count($newImages) : 1) : 0;
            
            if (empty($contentText) && ($remainingImagesCount + $newImagesCount) === 0) {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(400)
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => 'Post must have either text or images'
                    ]));
            }
            
           
            $connection = $this->Posts->getConnection();
            $connection->begin();
            
            
            $post->content_text = $contentText;
            if ($privacy && in_array($privacy, ['public', 'friends', 'private'])) {
                $post->privacy = $privacy;
            }
            $post->modified = new \DateTime();
            
            if (!$this->Posts->save($post)) {
                $connection->rollback();
                return $this->response
                    ->withType('application/json')
                    ->withStatus(500)
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => 'Failed to update post'
                    ]));
            }
            
            
            if (!empty($removedImageIds)) {
                $postImagesTable = $this->getTableLocator()->get('PostImages');
                foreach ($removedImageIds as $imageId) {
                    $image = $postImagesTable->get($imageId);
                    if ($image->post_id === $post->id) {
                       
                        $imagePath = WWW_ROOT . ltrim($image->image_path, '/');
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                        $postImagesTable->delete($image);
                    }
                }
            }
            
            // Handle new images
            if (!empty($newImages)) {
                $postImagesTable = $this->getTableLocator()->get('PostImages');
                
                // Ensure newImages is an array
                if (!is_array($newImages)) {
                    $newImages = [$newImages];
                }
                
                // Get current max sort order
                $maxSortOrder = $postImagesTable->find()
                    ->where(['post_id' => $post->id])
                    ->select(['max_order' => $postImagesTable->query()->func()->max('sort_order')])
                    ->first();
                $sortOrder = ($maxSortOrder && $maxSortOrder->max_order) ? $maxSortOrder->max_order + 1 : 0;
                
                foreach ($newImages as $uploadedFile) {
                    if (is_object($uploadedFile) && method_exists($uploadedFile, 'getError') && $uploadedFile->getError() === UPLOAD_ERR_OK) {
                        // Validate file type
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                        $fileType = $uploadedFile->getClientMediaType();
                        
                        if (!in_array($fileType, $allowedTypes)) {
                            $connection->rollback();
                            return $this->response
                                ->withType('application/json')
                                ->withStatus(400)
                                ->withStringBody(json_encode([
                                    'success' => false,
                                    'message' => 'Invalid file type'
                                ]));
                        }
                        
                        // Validate file size
                        $maxSize = 10 * 1024 * 1024;
                        if ($uploadedFile->getSize() > $maxSize) {
                            $connection->rollback();
                            return $this->response
                                ->withType('application/json')
                                ->withStatus(400)
                                ->withStringBody(json_encode([
                                    'success' => false,
                                    'message' => 'File too large'
                                ]));
                        }
                        
                        // Generate unique filename
                        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                        $filename = 'post_' . $post->id . '_' . time() . '_' . $sortOrder . '.' . $extension;
                        
                        $uploadDir = WWW_ROOT . 'img' . DS . 'post_uploads';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $uploadPath = $uploadDir . DS . $filename;
                        $uploadedFile->moveTo($uploadPath);
                        
                        // Create PostImage entity
                        $postImage = $postImagesTable->newEmptyEntity();
                        $postImage = $postImagesTable->patchEntity($postImage, [
                            'post_id' => $post->id,
                            'image_path' => '/img/post_uploads/' . $filename,
                            'sort_order' => $sortOrder
                        ]);
                        
                        if (!$postImagesTable->save($postImage)) {
                            throw new \Exception('Failed to save post image');
                        }
                        
                        $sortOrder++;
                    }
                }
            }
            
            $connection->commit();
            
            // Reload post with updated images
            $updatedPost = $this->Posts->get($id, [
                'contain' => ['Users', 'PostImages' => ['sort' => ['PostImages.sort_order' => 'ASC']]]
            ]);
            
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Post updated successfully',
                    'post' => $updatedPost
                ]));
                
        } catch (\Exception $e) {
            if (isset($connection)) {
                $connection->rollback();
            }
            Log::error('Error editing post: ' . $e->getMessage());
            return $this->response
                ->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'An error occurred while updating the post'
                ]));
        }
    }

    /**
     * Delete a post (soft delete)
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $this->viewBuilder()->disableAutoLayout();
        
        // Verify authentication
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ]));
        }

        $identity = $this->Authentication->getIdentity();
        $userId = null;
        
        // Extract user ID from identity
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
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]));
        }

        try {
            $post = $this->Posts->get($id);
            
            // Check if user owns this post
            if ($post->user_id !== $userId) {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(403)
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => 'You can only delete your own posts'
                    ]));
            }
            
            // Check if this post is a profile/cover photo post
            $isProfilePhotoPost = false;
            $isCoverPhotoPost = false;
            
            if (!empty($post->content_text)) {
                if (strpos($post->content_text, 'uploaded a new profile picture') !== false) {
                    $isProfilePhotoPost = true;
                } elseif (strpos($post->content_text, 'uploaded a new cover photo') !== false) {
                    $isCoverPhotoPost = true;
                }
            }
            
            // If it's a profile or cover photo post, clear the path in users table
            if ($isProfilePhotoPost || $isCoverPhotoPost) {
                $usersTable = $this->getTableLocator()->get('Users');
                $user = $usersTable->get($userId);
                
                if ($isProfilePhotoPost) {
                    // Delete the physical file if it exists
                    if (!empty($user->profile_photo_path)) {
                        $filePath = WWW_ROOT . ltrim($user->profile_photo_path, '/');
                        if (file_exists($filePath) && is_file($filePath)) {
                            @unlink($filePath);
                            Log::info("Deleted profile photo file: $filePath");
                        }
                    }
                    $user->profile_photo_path = null;
                    Log::info("Cleared profile_photo_path for user $userId");
                    
                    // Save only the profile_photo_path field to avoid affecting other fields
                    $user = $usersTable->patchEntity($user, ['profile_photo_path' => null], ['fields' => ['profile_photo_path']]);
                    if (!$usersTable->save($user)) {
                        Log::error('Failed to clear profile photo path for user ' . $userId);
                    }
                } elseif ($isCoverPhotoPost) {
                    // Delete the physical file if it exists
                    if (!empty($user->cover_photo_path)) {
                        $filePath = WWW_ROOT . ltrim($user->cover_photo_path, '/');
                        if (file_exists($filePath) && is_file($filePath)) {
                            @unlink($filePath);
                            Log::info("Deleted cover photo file: $filePath");
                        }
                    }
                    $user->cover_photo_path = null;
                    Log::info("Cleared cover_photo_path for user $userId");
                    
                    // Save only the cover_photo_path field to avoid affecting other fields
                    $user = $usersTable->patchEntity($user, ['cover_photo_path' => null], ['fields' => ['cover_photo_path']]);
                    if (!$usersTable->save($user)) {
                        Log::error('Failed to clear cover photo path for user ' . $userId);
                    }
                }
            }
            
            // Soft delete by setting deleted field
            $post->deleted = new \DateTime();
            
            if ($this->Posts->save($post)) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'message' => 'Post deleted successfully'
                    ]));
            } else {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(500)
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => 'Failed to delete post'
                    ]));
            }
        } catch (\Exception $e) {
            Log::error('Error deleting post: ' . $e->getMessage());
            return $this->response
                ->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'An error occurred while deleting the post'
                ]));
        }
    }

}
