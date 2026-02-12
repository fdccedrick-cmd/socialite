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
}
