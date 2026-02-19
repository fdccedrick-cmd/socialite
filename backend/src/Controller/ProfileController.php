<?php
declare(strict_types=1);

namespace App\Controller;

class ProfileController extends AppController
{
    public function view($id = null)
    {
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->redirect('/login');
        }

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
        
        // Handle both username and user ID
        $userId = null;
        if ($id) {
            // Check if $id is numeric (user ID) or string (username)
            if (is_numeric($id)) {
                $userId = (int)$id;
            } else {
                // It's a username, fetch the user by username
                $usersTable = $this->getTableLocator()->get('Users');
                $userByUsername = $usersTable->find()
                    ->where(['username' => $id])
                    ->first();
                
                if ($userByUsername) {
                    $userId = $userByUsername->id;
                } else {
                    // User not found
                    $this->Flash->error('User not found.');
                    return $this->redirect('/dashboard');
                }
            }
        } else {
            $userId = $currentUserId;
        }
        
       
        if ($this->request->getQuery('error') === 'network') {
            $this->Flash->error('Failed to update profile. Please try again.');
        }
        
       
        $usersTable = $this->getTableLocator()->get('Users');
        $userEntity = $usersTable->get($userId);
        $user = $userEntity->toArray();
        
        
        $user['full_name'] = $user['full_name'] ?? $user['username'] ?? 'User';
        $user['username'] = $user['username'] ?? 'user';
        $user['profile_photo_path'] = $user['profile_photo_path'] ?? null;
        $user['bio'] = $user['bio'] ?? null;

        
        foreach (['created', 'modified'] as $dtField) {
            if (!empty($user[$dtField]) && $user[$dtField] instanceof \DateTimeInterface) {
                $user[$dtField] = $user[$dtField]->format(DATE_ATOM);
            }
        }
        
        
        // Check friendship status if viewing another user's profile
        $friendshipStatus = null;
        $friendshipId = null;
        $isOwnProfile = ($userId == $currentUserId);
        $friendshipsTable = $this->getTableLocator()->get('Friendships');
        
        // Get friends count for the profile user
        $friendsCount = count($friendshipsTable->getFriends($userId));
        
        if (!$isOwnProfile) {
            $friendship = $friendshipsTable->getFriendshipStatus($currentUserId, $userId);
            
            if ($friendship) {
                $friendshipStatus = $friendship->status;
                $friendshipId = $friendship->id;
                // Check if current user is the sender or receiver
                $isSender = $friendship->user_id == $currentUserId;
                $this->set('isSender', $isSender);
            }
            
            // Get mutual friends count
            $mutualFriendsCount = $friendshipsTable->getMutualFriendsCount($currentUserId, $userId);
            $this->set('mutualFriendsCount', $mutualFriendsCount);
        }
        
        $postsTable = $this->getTableLocator()->get('Posts');
        $likesTable = $this->getTableLocator()->get('Likes');
        
        // Check if current user is a friend of the profile user
        $isFriend = false;
        if (!$isOwnProfile) {
            $friendship = $friendshipsTable->find()
                ->where([
                    'status' => 'accepted',
                    'OR' => [
                        ['user_id' => $currentUserId, 'friend_id' => $userId],
                        ['user_id' => $userId, 'friend_id' => $currentUserId]
                    ]
                ])
                ->first();
            $isFriend = !empty($friendship);
        }
        
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
        
        $postsArray = [];
        // compute total likes across all posts with a single query for reliability
        $postIds = array_map(function($p) { return $p->id; }, $posts);
        if (!empty($postIds)) {
            $userLikeCount = (int)$likesTable->find()
                ->where([
                    "LOWER(target_type) =" => 'post',
                    'target_id IN' => $postIds,
                    'post_image_id IS' => null
                ])
                ->count();
        } else {
            $userLikeCount = 0;
        }

        // Debug logging: show post IDs and computed like count
        try {
            error_log('Profile view - userId: ' . $userId . ' postIds: ' . json_encode($postIds));
            error_log('Profile view - computed userLikeCount: ' . $userLikeCount);
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        foreach ($posts as $post) {
            // Apply privacy filtering
            $canView = false;
            if ($isOwnProfile) {
                $canView = true; // Show all posts on own profile
            } else {
                if ($post->privacy === 'public') {
                    $canView = true;
                } elseif ($post->privacy === 'friends' && $isFriend) {
                    $canView = true;
                }
                // private posts are never shown on other people's profiles
            }
            
            if (!$canView) {
                continue;
            }
            
            $postData = $post->toArray();
            if (!empty($postData['created']) && $postData['created'] instanceof \DateTimeInterface) {
                $postData['created'] = $postData['created']->format(DATE_ATOM);
            }
            if (!empty($postData['modified']) && $postData['modified'] instanceof \DateTimeInterface) {
                $postData['modified'] = $postData['modified']->format(DATE_ATOM);
            }
            
           
            $postData['like_count'] = (int)$likesTable->find()
                ->where([
                    "LOWER(target_type) =" => 'post',
                    'target_id' => $post->id,
                    'post_image_id IS' => null
                ])
                ->count();
            
            
            $postData['is_liked'] = $likesTable->find()
                ->where([
                    "LOWER(target_type) =" => 'post',
                    'target_id' => $post->id,
                    'user_id' => $currentUserId,
                    'post_image_id IS' => null
                ])
                ->count() > 0;
            
            $commentsTable = $this->getTableLocator()->get('Comments');
            $postData['comment_count'] = $commentsTable->find('active')
                ->where(['post_id' => $post->id])
                ->count();
            
            $postsArray[] = $postData;
        }
        
        $postCount = count($postsArray);

        $this->set(compact('user', 'postsArray', 'postCount', 'currentUserId', 'userLikeCount', 'postIds', 'isOwnProfile', 'friendshipStatus', 'friendshipId', 'friendsCount'));
    }

    public function update()
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->disableAutoLayout();
        error_log('Profile update - update() called');
        
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access',
                    'reached_controller' => true
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
                    'message' => 'User not found',
                    'reached_controller' => true
                ]));
        }

        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get($userId);
        $data = [];
        $errors = [];
        // Debug raw PHP files array
        try {
            error_log('Profile update - _FILES: ' . json_encode($_FILES));
        } catch (\Throwable $e) {
            // ignore
        }

        $bio = $this->request->getData('bio');
        // Allow empty bio - will be stored as null and show "No bio yet" in UI
        $data['bio'] = !empty($bio) ? trim($bio) : null;

        $fullName = $this->request->getData('full_name');
        if (!empty($fullName)) {
            $data['full_name'] = trim($fullName);
        } else {
            $errors['full_name'] = 'Full name is required';
        }

        $uploadedFile = $this->request->getData('profile_picture');
        // Log uploaded file info for debugging
        try {
            error_log('Profile update - uploadedFile raw: ' . json_encode(is_object($uploadedFile) ? get_class($uploadedFile) : $uploadedFile));
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        if ($uploadedFile && is_object($uploadedFile) && method_exists($uploadedFile, 'getError') && $uploadedFile->getError() === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $fileType = $uploadedFile->getClientMediaType();
            
            if (!in_array($fileType, $allowedTypes)) {
                $errors['profile_picture'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
            } else {
                $maxSize = 5 * 1024 * 1024;
                if ($uploadedFile->getSize() > $maxSize) {
                    $errors['profile_picture'] = 'File size must be less than 5MB.';
                } else {
                    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
                    $uploadPath = WWW_ROOT . 'img' . DS . 'profile_uploads' . DS . $filename;
                    
                    try {
                        if (!is_dir(dirname($uploadPath))) @mkdir(dirname($uploadPath), 0755, true);
                        $uploadedFile->moveTo($uploadPath);
                        $data['profile_photo_path'] = '/img/profile_uploads/' . $filename;
                        
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
        // Fallback: sometimes uploaded file may be provided as array (from PHP native $_FILES)
        elseif (is_array($uploadedFile) && isset($uploadedFile['tmp_name']) && isset($uploadedFile['error'])) {
            try {
                error_log('Profile update - handling array-style upload: ' . json_encode([$uploadedFile['name'] ?? null, $uploadedFile['error'] ?? null]));
                if ($uploadedFile['error'] === UPLOAD_ERR_OK && is_uploaded_file($uploadedFile['tmp_name'])) {
                    $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
                    $uploadPath = WWW_ROOT . 'img' . DS . 'profile_uploads' . DS . $filename;
                    if (!is_dir(dirname($uploadPath))) @mkdir(dirname($uploadPath), 0755, true);
                    if (move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
                        $data['profile_photo_path'] = '/img/profile_uploads/' . $filename;
                        if (!empty($user->profile_photo_path) && $user->profile_photo_path !== '/img/profile_uploads/' . $filename) {
                            $oldPath = WWW_ROOT . ltrim($user->profile_photo_path, '/');
                            if (file_exists($oldPath) && is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                    } else {
                        $errors['profile_picture'] = 'Failed to move uploaded file.';
                    }
                } else {
                    $errors['profile_picture'] = 'No valid uploaded file found.';
                }
            } catch (\Throwable $e) {
                error_log('Profile update - array upload fallback error: ' . $e->getMessage());
                $errors['profile_picture'] = 'Failed to process uploaded file.';
            }
        }

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
                
                $hasher = new \Authentication\PasswordHasher\DefaultPasswordHasher();
                if (!$hasher->check($currentPassword, $user->password_hash)) {
                    $errors['current_password'] = 'Current password is incorrect.';
                } else {
                    $data['password_hash'] = $newPassword;
                }
            }
        }

        
        if (!empty($errors)) {
            $this->Flash->error('Validation failed. Please check your input.');
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'errors' => $errors
                ]));
        }


        // Debug: log incoming request data and prepared $data (redact passwords)
        try {
            $raw = $this->request->getData();
            $logRaw = $raw;
            if (is_array($logRaw) && isset($logRaw['current_password'])) {
                $logRaw['current_password'] = '[REDACTED]';
            }
            if (is_array($logRaw) && isset($logRaw['new_password'])) {
                $logRaw['new_password'] = '[REDACTED]';
            }
            $logData = $data;
            if (isset($logData['password_hash'])) {
                $logData['password_hash'] = '[REDACTED]';
            }
            error_log('Profile update - raw post data: ' . json_encode($logRaw));
            error_log('Profile update - prepared data: ' . json_encode($logData));
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        
        $oldProfilePath = $user->profile_photo_path;
        $user = $usersTable->patchEntity($user, $data);

        try {
            error_log('Profile update - patched entity: ' . json_encode($user->toArray()));
        } catch (\Throwable $e) {
            // ignore
        }
        
        try {
            $saveResult = $usersTable->save($user);
            error_log('Profile update - save result: ' . ($saveResult ? 'true' : 'false'));

            if ($saveResult) {
                
                try {
                    $fresh = $usersTable->get($userId);
                    error_log('Profile update - fresh from DB after save: ' . json_encode($fresh->toArray()));
                } catch (\Throwable $e) {
                    error_log('Profile update - failed to fetch fresh user: ' . $e->getMessage());
                }

                $this->Authentication->setIdentity($user);
                $this->Flash->success('Profile updated successfully!');

                $responseData = [
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'bio' => $user->bio,
                    'profile_photo_path' => $user->profile_photo_path
                ];

                // If profile photo changed, create a post announcing the new profile picture
                try {
                    if (!empty($data['profile_photo_path']) && $data['profile_photo_path'] !== $oldProfilePath) {
                        // Copy the profile picture to post_uploads to preserve it when profile picture changes
                        $sourceFile = WWW_ROOT . ltrim($data['profile_photo_path'], '/');
                        $extension = pathinfo($sourceFile, PATHINFO_EXTENSION);
                        $postFilename = 'post_' . $userId . '_' . time() . '_profile.' . $extension;
                        $postUploadDir = WWW_ROOT . 'img' . DS . 'post_uploads';
                        $postUploadPath = $postUploadDir . DS . $postFilename;
                        
                        // Ensure post_uploads directory exists
                        if (!is_dir($postUploadDir)) {
                            @mkdir($postUploadDir, 0755, true);
                        }
                        
                        // Copy the file to post_uploads
                        $postImagePath = null;
                        if (file_exists($sourceFile) && copy($sourceFile, $postUploadPath)) {
                            $postImagePath = '/img/post_uploads/' . $postFilename;
                            error_log('Profile update - copied profile picture to post_uploads: ' . $postImagePath);
                        } else {
                            // If copy fails, log error but still create post without image
                            error_log('Profile update - failed to copy profile picture from ' . $sourceFile . ' to ' . $postUploadPath);
                        }
                        
                        // Create the post
                        $postsTable = $this->getTableLocator()->get('Posts');
                        $post = $postsTable->newEmptyEntity();
                        $post->user_id = $userId;
                        $post->content_text = trim($user->full_name) . ' uploaded a new profile picture';
                        $post->privacy = 'public';
                        
                        if ($postsTable->save($post) && $postImagePath) {
                            $postImagesTable = $this->getTableLocator()->get('PostImages');
                            $postImage = $postImagesTable->newEmptyEntity();
                            $postImage->post_id = $post->id;
                            $postImage->image_path = $postImagePath; // Use the copied path in post_uploads
                            $postImage->sort_order = 0;
                            
                            if ($postImagesTable->save($postImage)) {
                                error_log('Profile update - created post with image: ' . $postImagePath);
                            } else {
                                error_log('Profile update - failed to save post image');
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    error_log('Profile update - failed to create post for profile photo change: ' . $e->getMessage());
                }

                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'user' => $responseData,
                        'reached_controller' => true
                    ]));
            }
        } catch (\Throwable $e) {
            error_log('Profile update - save exception: ' . $e->getMessage());
        }

        $validationErrors = $user->getErrors();
        error_log('Profile update - validation errors: ' . json_encode($validationErrors));
        $this->Flash->error('Failed to update profile. Please check your input.');
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'errors' => $validationErrors,
                'reached_controller' => true
            ]));
    }
}
