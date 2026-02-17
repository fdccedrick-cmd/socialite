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
        
        $userId = $id ? (int)$id : $currentUserId;
        
       
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
        
        $postsArray = [];
        // compute total likes across all posts with a single query for reliability
        $postIds = array_map(function($p) { return $p->id; }, $posts);
        if (!empty($postIds)) {
            $userLikeCount = (int)$likesTable->find()
                ->where(["LOWER(target_type) =" => 'post', 'target_id IN' => $postIds])
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
            $postData = $post->toArray();
            if (!empty($postData['created']) && $postData['created'] instanceof \DateTimeInterface) {
                $postData['created'] = $postData['created']->format(DATE_ATOM);
            }
            if (!empty($postData['modified']) && $postData['modified'] instanceof \DateTimeInterface) {
                $postData['modified'] = $postData['modified']->format(DATE_ATOM);
            }
            
           
            $postData['like_count'] = (int)$likesTable->find()
                ->where(["LOWER(target_type) =" => 'post', 'target_id' => $post->id])
                ->count();
            
            
            $postData['is_liked'] = $likesTable->find()
                ->where([
                    "LOWER(target_type) =" => 'post',
                    'target_id' => $post->id,
                    'user_id' => $currentUserId
                ])
                ->count() > 0;
            
            $commentsTable = $this->getTableLocator()->get('Comments');
            $postData['comment_count'] = $commentsTable->find('active')
                ->where(['post_id' => $post->id])
                ->count();
            
            $postsArray[] = $postData;
        }
        
        $postCount = count($postsArray);

        $this->set(compact('user', 'postsArray', 'postCount', 'currentUserId', 'userLikeCount', 'postIds'));
    }

    public function update()
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

        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get($userId);
        $data = [];
        $errors = [];

        $bio = $this->request->getData('bio');
        if (!empty($bio)) {
            $data['bio'] = trim($bio);
        }

        $fullName = $this->request->getData('full_name');
        if (!empty($fullName)) {
            $data['full_name'] = trim($fullName);
        } else {
            $errors['full_name'] = 'Full name is required';
        }

        $uploadedFile = $this->request->getData('profile_picture');
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
                // Re-fetch fresh record from DB to confirm persisted values
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

                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'user' => $responseData
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
                'errors' => $validationErrors
            ]));
    }
}
