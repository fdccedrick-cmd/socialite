<?php
declare(strict_types=1);

namespace App\Controller;

use App\Utility\ImageProcessor;

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
        $user['cover_photo_path'] = $user['cover_photo_path'] ?? null;
        $user['bio'] = $user['bio'] ?? null;
        $user['address'] = $user['address'] ?? null;
        $user['relationship_status'] = $user['relationship_status'] ?? null;
        $user['contact_links'] = $user['contact_links'] ?? null;

        
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

        // Fetch current user's data for comment inputs (for JavaScript)
        $commentUser = null;
        if ($currentUserId) {
            $currentUserEntity = $usersTable->find()
                ->where(['id' => $currentUserId])
                ->first();
            if ($currentUserEntity) {
                $commentUser = [
                    'id' => $currentUserEntity->id,
                    'username' => $currentUserEntity->username ?? 'user',
                    'full_name' => $currentUserEntity->full_name ?? $currentUserEntity->username ?? 'User',
                    'avatar' => $currentUserEntity->profile_photo_path ?? '/img/default/default_avatar.jpg'
                ];
            }
        }

        $this->set(compact('user', 'postsArray', 'postCount', 'currentUserId', 'commentUser', 'userLikeCount', 'postIds', 'isOwnProfile', 'friendshipStatus', 'friendshipId', 'friendsCount'));
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

        // Only update bio if it's explicitly provided in the request
        if ($this->request->getData('bio') !== null) {
            $bio = $this->request->getData('bio');
            // Allow empty bio - will be stored as null and show "No bio yet" in UI
            $data['bio'] = !empty($bio) ? trim($bio) : null;
        }

        // Only update address if it's explicitly provided in the request
        if ($this->request->getData('address') !== null) {
            $address = $this->request->getData('address');
            $data['address'] = !empty($address) ? trim($address) : null;
            error_log('Profile update - address received: ' . ($address ?? 'NULL'));
        }

        // Only update relationship status if it's explicitly provided in the request
        if ($this->request->getData('relationship_status') !== null) {
            $relationshipStatus = $this->request->getData('relationship_status');
            error_log('Profile update - relationship_status received: ' . ($relationshipStatus ?? 'NULL'));
            if (!empty($relationshipStatus)) {
                $validStatuses = ['single', 'taken', 'married'];
                if (in_array($relationshipStatus, $validStatuses)) {
                    $data['relationship_status'] = $relationshipStatus;
                }
            } else {
                $data['relationship_status'] = null;
            }
        }

        // Only update contact links if it's explicitly provided in the request
        if ($this->request->getData('contact_links') !== null) {
            $contactLinks = $this->request->getData('contact_links');
            error_log('Profile update - contact_links received: ' . ($contactLinks ?? 'NULL'));
            if (!empty($contactLinks)) {
                if (is_array($contactLinks)) {
                    $data['contact_links'] = json_encode($contactLinks);
                } elseif (is_string($contactLinks)) {
                    // Validate it's valid JSON
                    $decoded = json_decode($contactLinks, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['contact_links'] = $contactLinks;
                    }
                }
            } else {
                $data['contact_links'] = null;
            }
        }

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
                $maxSize = 20 * 1024 * 1024; // 20MB for profile photos
                if ($uploadedFile->getSize() > $maxSize) {
                    $errors['profile_picture'] = 'File size must be less than 20MB.';
                } else {
                    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
                    $uploadPath = WWW_ROOT . 'img' . DS . 'profile_uploads' . DS . $filename;
                    
                    try {
                        if (!is_dir(dirname($uploadPath))) @mkdir(dirname($uploadPath), 0755, true);
                        
                        // Skip processing for GIFs to preserve animation
                        if ($fileType === 'image/gif') {
                            $uploadedFile->moveTo($uploadPath);
                            error_log("ProfileController: Skipping processing for GIF profile photo $filename to preserve animation");
                        }
                        // Process other images (resize, compress, sharpen)
                        else {
                            $tempPath = dirname($uploadPath) . DS . 'temp_' . $filename;
                            $uploadedFile->moveTo($tempPath);
                            
                            if (ImageProcessor::isAvailable()) {
                                $processSuccess = ImageProcessor::processImage($tempPath, $uploadPath);
                                
                                if ($processSuccess) {
                                    @unlink($tempPath);
                                    error_log("ProfileController: Successfully processed profile photo $filename");
                                } else {
                                    @rename($tempPath, $uploadPath);
                                    error_log("ProfileController: Failed to process profile photo, using original");
                                }
                            } else {
                                @rename($tempPath, $uploadPath);
                                error_log("ProfileController: GD not available, using original profile photo");
                            }
                        }
                        
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

        // Handle cover photo upload
        $uploadedCoverPhoto = $this->request->getData('cover_photo');
        try {
            error_log('Profile update - cover photo raw: ' . json_encode(is_object($uploadedCoverPhoto) ? get_class($uploadedCoverPhoto) : $uploadedCoverPhoto));
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        if ($uploadedCoverPhoto && is_object($uploadedCoverPhoto) && method_exists($uploadedCoverPhoto, 'getError') && $uploadedCoverPhoto->getError() === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $fileType = $uploadedCoverPhoto->getClientMediaType();
            
            if (!in_array($fileType, $allowedTypes)) {
                $errors['cover_photo'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
            } else {
                $maxSize = 50 * 1024 * 1024; // 50MB for cover photos
                if ($uploadedCoverPhoto->getSize() > $maxSize) {
                    $errors['cover_photo'] = 'File size must be less than 50MB.';
                } else {
                    $extension = pathinfo($uploadedCoverPhoto->getClientFilename(), PATHINFO_EXTENSION);
                    $filename = 'cover_' . $userId . '_' . time() . '.' . $extension;
                    $uploadPath = WWW_ROOT . 'img' . DS . 'cover_photo_uploads' . DS . $filename;
                    
                    try {
                        if (!is_dir(dirname($uploadPath))) @mkdir(dirname($uploadPath), 0755, true);
                        
                        // Skip processing for GIFs to preserve animation
                        if ($fileType === 'image/gif') {
                            $uploadedCoverPhoto->moveTo($uploadPath);
                            error_log("ProfileController: Skipping processing for GIF cover photo $filename to preserve animation");
                        }
                        // Process other images (resize, compress, sharpen)
                        else {
                            $tempPath = dirname($uploadPath) . DS . 'temp_' . $filename;
                            $uploadedCoverPhoto->moveTo($tempPath);
                            
                            if (ImageProcessor::isAvailable()) {
                                $processSuccess = ImageProcessor::processImage($tempPath, $uploadPath);
                                
                                if ($processSuccess) {
                                    @unlink($tempPath);
                                    error_log("ProfileController: Successfully processed cover photo $filename");
                                } else {
                                    @rename($tempPath, $uploadPath);
                                    error_log("ProfileController: Failed to process cover photo, using original");
                                }
                            } else {
                                @rename($tempPath, $uploadPath);
                                error_log("ProfileController: GD  not available, using original cover photo");
                            }
                        }
                        
                        $data['cover_photo_path'] = '/img/cover_photo_uploads/' . $filename;
                        
                        if (!empty($user->cover_photo_path) && $user->cover_photo_path !== '/img/cover_photo_uploads/' . $filename) {
                            $oldPath = WWW_ROOT . ltrim($user->cover_photo_path, '/');
                            if (file_exists($oldPath) && is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                    } catch (\Exception $e) {
                        $errors['cover_photo'] = 'Failed to upload cover photo.';
                    }
                }
            }
        }
        // Fallback: sometimes uploaded file may be provided as array
        elseif (is_array($uploadedCoverPhoto) && isset($uploadedCoverPhoto['tmp_name']) && isset($uploadedCoverPhoto['error'])) {
            try {
                error_log('Profile update - handling array-style cover photo upload: ' . json_encode([$uploadedCoverPhoto['name'] ?? null, $uploadedCoverPhoto['error'] ?? null]));
                if ($uploadedCoverPhoto['error'] === UPLOAD_ERR_OK && is_uploaded_file($uploadedCoverPhoto['tmp_name'])) {
                    $extension = pathinfo($uploadedCoverPhoto['name'], PATHINFO_EXTENSION);
                    $filename = 'cover_' . $userId . '_' . time() . '.' . $extension;
                    $uploadPath = WWW_ROOT . 'img' . DS . 'cover_photo_uploads' . DS . $filename;
                    if (!is_dir(dirname($uploadPath))) @mkdir(dirname($uploadPath), 0755, true);
                    
                    // Check if it's a GIF
                    $isGif = (strtolower($extension) === 'gif');
                    
                    // Skip processing for GIFs to preserve animation
                    if ($isGif) {
                        if (move_uploaded_file($uploadedCoverPhoto['tmp_name'], $uploadPath)) {
                            error_log("ProfileController: Skipping processing for GIF cover photo (array) $filename to preserve animation");
                        }
                    }
                    // Process other images
                    else {
                        $tempPath = dirname($uploadPath) . DS . 'temp_' . $filename;
                        if (move_uploaded_file($uploadedCoverPhoto['tmp_name'], $tempPath)) {
                            if (ImageProcessor::isAvailable()) {
                                $processSuccess = ImageProcessor::processImage($tempPath, $uploadPath);
                                
                                if ($processSuccess) {
                                    @unlink($tempPath);
                                    error_log("ProfileController: Successfully processed cover photo (array) $filename");
                                } else {
                                    @rename($tempPath, $uploadPath);
                                    error_log("ProfileController: Failed to process cover photo (array), using original");
                                }
                            } else {
                                @rename($tempPath, $uploadPath);
                                error_log("ProfileController: GD not available for cover photo (array), using original");
                            }
                        }
                    }
                    
                    if (file_exists($uploadPath)) {
                        
                        $data['cover_photo_path'] = '/img/cover_photo_uploads/' . $filename;
                        if (!empty($user->cover_photo_path) && $user->cover_photo_path !== '/img/cover_photo_uploads/' . $filename) {
                            $oldPath = WWW_ROOT . ltrim($user->cover_photo_path, '/');
                            if (file_exists($oldPath) && is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                    } else {
                        $errors['cover_photo'] = 'Failed to move uploaded cover photo.';
                    }
                } else {
                    $errors['cover_photo'] = 'No valid uploaded cover photo found.';
                }
            } catch (\Throwable $e) {
                error_log('Profile update - cover photo array upload fallback error: ' . $e->getMessage());
                $errors['cover_photo'] = 'Failed to process uploaded cover photo.';
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
        $oldCoverPath = $user->cover_photo_path;
        $user = $usersTable->patchEntity($user, $data);

        try {
            error_log('Profile update - patched entity: ' . json_encode($user->toArray()));
        } catch (\Throwable $e) {
            // ignore
        }
        
        try {
            $saveResult = $usersTable->save($user);
            error_log('Profile update - save result: ' . ($saveResult ? 'true' : 'false'));
            
            // Check for validation errors
            if (!$saveResult) {
                $validationErrors = $user->getErrors();
                error_log('Profile update - VALIDATION ERRORS: ' . json_encode($validationErrors));
            }

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
                    'profile_photo_path' => $user->profile_photo_path,
                    'cover_photo_path' => $user->cover_photo_path,
                    'address' => $user->address,
                    'relationship_status' => $user->relationship_status,
                    'contact_links' => $user->contact_links
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

                // If cover photo changed, create a post announcing the new cover photo
                try {
                    if (!empty($data['cover_photo_path']) && $data['cover_photo_path'] !== $oldCoverPath) {
                        // Copy the cover photo to post_uploads to preserve it when cover photo changes
                        $sourceFile = WWW_ROOT . ltrim($data['cover_photo_path'], '/');
                        $extension = pathinfo($sourceFile, PATHINFO_EXTENSION);
                        $postFilename = 'post_' . $userId . '_' . time() . '_cover.' . $extension;
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
                            error_log('Profile update - copied cover photo to post_uploads: ' . $postImagePath);
                        } else {
                            error_log('Profile update - failed to copy cover photo from ' . $sourceFile . ' to ' . $postUploadPath);
                        }
                        
                        // Create the post
                        $postsTable = $this->getTableLocator()->get('Posts');
                        $post = $postsTable->newEmptyEntity();
                        $post->user_id = $userId;
                        $post->content_text = trim($user->full_name) . ' uploaded a new cover photo';
                        $post->privacy = 'public';
                        
                        if ($postsTable->save($post) && $postImagePath) {
                            $postImagesTable = $this->getTableLocator()->get('PostImages');
                            $postImage = $postImagesTable->newEmptyEntity();
                            $postImage->post_id = $post->id;
                            $postImage->image_path = $postImagePath;
                            $postImage->sort_order = 0;
                            
                            if ($postImagesTable->save($postImage)) {
                                error_log('Profile update - created post with cover photo: ' . $postImagePath);
                            } else {
                                error_log('Profile update - failed to save cover photo post image');
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    error_log('Profile update - failed to create post for cover photo change: ' . $e->getMessage());
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
