<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

class PostsController extends AppController
{
    public function create()
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->disableAutoLayout();
        
        // Verify authentication
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
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]));
        }

        // Get post data
        $contentText = $this->request->getData('content_text');
        $privacy = $this->request->getData('privacy', 'public');
        $uploadedFiles = $this->request->getData('post_images');

        // Validate at least content or images
        if (empty($contentText) && empty($uploadedFiles)) {
            $this->Flash->error('Post must have either text or images.');
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Post must have either text or images'
                ]));
        }

        // Create post entity
        $post = $this->Posts->newEmptyEntity();
        $postData = [
            'user_id' => $userId,
            'content_text' => $contentText,
            'privacy' => $privacy
        ];

        $post = $this->Posts->patchEntity($post, $postData);

        // Use transaction to save post and images together
        $connection = $this->Posts->getConnection();
        $connection->begin();

        try {
            // Save the post
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

            // Handle multiple image uploads
            if (!empty($uploadedFiles)) {
                $postImagesTable = $this->getTableLocator()->get('PostImages');
                
                // Ensure uploadedFiles is an array
                if (!is_array($uploadedFiles)) {
                    $uploadedFiles = [$uploadedFiles];
                }

                $sortOrder = 0;
                foreach ($uploadedFiles as $uploadedFile) {
                    // Check if it's a valid uploaded file
                    if (is_object($uploadedFile) && method_exists($uploadedFile, 'getError') && $uploadedFile->getError() === UPLOAD_ERR_OK) {
                        // Validate file type
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

                        // Validate file size (10MB max per image)
                        $maxSize = 10 * 1024 * 1024;
                        if ($uploadedFile->getSize() > $maxSize) {
                            $connection->rollback();
                            $this->Flash->error('File size must be less than 10MB per image.');
                            return $this->response
                                ->withType('application/json')
                                ->withStringBody(json_encode([
                                    'success' => false,
                                    'message' => 'File too large'
                                ]));
                        }

                        // Generate unique filename
                        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                        $filename = 'post_' . $post->id . '_' . time() . '_' . $sortOrder . '.' . $extension;
                        
                        // Create directory if it doesn't exist
                        $uploadDir = WWW_ROOT . 'img' . DS . 'post_uploads';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $uploadPath = $uploadDir . DS . $filename;
                        
                        // Move uploaded file
                        try {
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
     * Edit/Update a post
     */
    public function edit($id = null)
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
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
            $post = $this->Posts->get($id, [
                'contain' => ['PostImages']
            ]);
            
            // Check if user owns this post
            if ($post->user_id !== $userId) {
                return $this->response
                    ->withType('application/json')
                    ->withStatus(403)
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => 'You can only edit your own posts'
                    ]));
            }
            
            // Get updated content
            $contentText = $this->request->getData('content_text');
            $removedImageIds = $this->request->getData('removed_images', []);
            $newImages = $this->request->getData('new_images');
            
            // Validate at least content or images
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
            
            // Start transaction
            $connection = $this->Posts->getConnection();
            $connection->begin();
            
            // Update post text
            $post->content_text = $contentText;
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
            
            // Handle removed images
            if (!empty($removedImageIds)) {
                $postImagesTable = $this->getTableLocator()->get('PostImages');
                foreach ($removedImageIds as $imageId) {
                    $image = $postImagesTable->get($imageId);
                    if ($image->post_id === $post->id) {
                        // Delete file from filesystem
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
