<?php
declare(strict_types=1);

namespace App\Controller;

class ProfileController extends AppController
{
    public function view($id = null)
    {
        // Use authenticated identity for profile data
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->redirect('/login');
        }

        $identity = $this->Authentication->getIdentity();
        
        // Get current logged-in user ID
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
        
        // Determine which user profile to show
        // If $id is provided, show that user's profile; otherwise show logged-in user's profile
        $userId = $id ? (int)$id : $currentUserId;
        
        // Check for error query parameter
        if ($this->request->getQuery('error') === 'network') {
            $this->Flash->error('Failed to update profile. Please try again.');
        }
        
        // Fetch fresh user data from database
        $usersTable = $this->getTableLocator()->get('Users');
        $userEntity = $usersTable->get($userId);
        $user = $userEntity->toArray();
        
        // Ensure all required fields exist with defaults
        $user['full_name'] = $user['full_name'] ?? $user['username'] ?? 'User';
        $user['username'] = $user['username'] ?? 'user';
        $user['profile_photo_path'] = $user['profile_photo_path'] ?? null;
        $user['bio'] = $user['bio'] ?? null;

        // Convert DateTime objects to ISO strings for client-side parsing
        foreach (['created', 'modified'] as $dtField) {
            if (!empty($user[$dtField]) && $user[$dtField] instanceof \DateTimeInterface) {
                $user[$dtField] = $user[$dtField]->format(DATE_ATOM);
            }
        }
        
        // Fetch user's posts with images, ordered by most recent
        $postsTable = $this->getTableLocator()->get('Posts');
        $likesTable = $this->getTableLocator()->get('Likes');
        
        $posts = $postsTable->find()
            ->where([
                'Posts.user_id' => $userId,
                'Posts.deleted IS' => null
            ])
            ->contain([
                'Users',
                'PostImages' => ['sort' => ['PostImages.sort_order' => 'ASC']]
            ])
            ->order(['Posts.created' => 'DESC'])
            ->toArray();
        
        // Convert posts to array with formatted dates and like data
        $postsArray = [];
        foreach ($posts as $post) {
            $postData = $post->toArray();
            if (!empty($postData['created']) && $postData['created'] instanceof \DateTimeInterface) {
                $postData['created'] = $postData['created']->format(DATE_ATOM);
            }
            if (!empty($postData['modified']) && $postData['modified'] instanceof \DateTimeInterface) {
                $postData['modified'] = $postData['modified']->format(DATE_ATOM);
            }
            
            // Add like count
            $postData['like_count'] = $likesTable->find()
                ->where(['target_type' => 'Post', 'target_id' => $post->id])
                ->count();
            
            // Check if current user has liked this post (use logged-in user, not profile user)
            $postData['is_liked'] = $likesTable->find()
                ->where([
                    'target_type' => 'Post',
                    'target_id' => $post->id,
                    'user_id' => $currentUserId
                ])
                ->count() > 0;
            
            // Add comment count
            $commentsTable = $this->getTableLocator()->get('Comments');
            $postData['comment_count'] = $commentsTable->find('active')
                ->where(['post_id' => $post->id])
                ->count();
            
            $postsArray[] = $postData;
        }
        
        // Count posts for stats
        $postCount = count($postsArray);

        $this->set(compact('user', 'postsArray', 'postCount', 'currentUserId'));
    }

    public function update()
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

        // Get user from database
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get($userId);
        $data = [];
        $errors = [];

        // Handle full name update
        $fullName = $this->request->getData('full_name');
        if (!empty($fullName)) {
            $data['full_name'] = trim($fullName);
        } else {
            $errors['full_name'] = 'Full name is required';
        }

        // Handle profile picture upload
        $uploadedFile = $this->request->getData('profile_picture');
        if ($uploadedFile && is_object($uploadedFile) && method_exists($uploadedFile, 'getError') && $uploadedFile->getError() === UPLOAD_ERR_OK) {
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $fileType = $uploadedFile->getClientMediaType();
            
            if (!in_array($fileType, $allowedTypes)) {
                $errors['profile_picture'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
            } else {
                // Validate file size (5MB max)
                $maxSize = 5 * 1024 * 1024;
                if ($uploadedFile->getSize() > $maxSize) {
                    $errors['profile_picture'] = 'File size must be less than 5MB.';
                } else {
                    // Generate unique filename
                    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
                    $uploadPath = WWW_ROOT . 'img' . DS . 'profile_uploads' . DS . $filename;
                    
                    // Move uploaded file
                    try {
                        $uploadedFile->moveTo($uploadPath);
                        $data['profile_photo_path'] = '/img/profile_uploads/' . $filename;
                        
                        // Delete old profile picture if exists
                        if (!empty($user->profile_photo_path) && $user->profile_photo_path !== '/img/profile_uploads/' . $filename) {
                            $oldPath = WWW_ROOT . ltrim($user->profile_photo_path, '/');
                            if (file_exists($oldPath) && is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                    } catch (\Exception $e) {
                        $errors['profile_picture'] = 'Failed to upload file.';
                    }
                }
            }
        }

        // Handle password update
        $currentPassword = $this->request->getData('current_password');
        $newPassword = $this->request->getData('new_password');
        
        if (!empty($currentPassword) || !empty($newPassword)) {
            if (empty($currentPassword)) {
                $errors['current_password'] = 'Current password is required to change password.';
            } elseif (empty($newPassword)) {
                $errors['new_password'] = 'New password is required.';
            } elseif (strlen($newPassword) < 6) {
                $errors['new_password'] = 'Password must be at least 6 characters.';
            } else {
                // Verify current password
                $hasher = new \Authentication\PasswordHasher\DefaultPasswordHasher();
                if (!$hasher->check($currentPassword, $user->password_hash)) {
                    $errors['current_password'] = 'Current password is incorrect.';
                } else {
                    // Update password
                    $data['password_hash'] = $newPassword;
                }
            }
        }

        // Return errors if any
        if (!empty($errors)) {
            $this->Flash->error('Validation failed. Please check your input.');
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'errors' => $errors
                ]));
        }

        // Update user
        $user = $usersTable->patchEntity($user, $data);
        
        if ($usersTable->save($user)) {
            // Refresh the authentication session with updated user data
            $this->Authentication->setIdentity($user);
            
            // Set Flash success message
            $this->Flash->success('Profile updated successfully!');
            
            // Prepare response data
            $responseData = [
                'full_name' => $user->full_name,
                'username' => $user->username,
                'profile_photo_path' => $user->profile_photo_path
            ];
            
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'user' => $responseData
                ]));
        } else {
            $validationErrors = $user->getErrors();
            $this->Flash->error('Failed to update profile. Please check your input.');
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'errors' => $validationErrors
                ]));
        }
    }
}
