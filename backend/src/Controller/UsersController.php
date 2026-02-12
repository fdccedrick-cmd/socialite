<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Log\Log;
use Cake\Http\Cookie\Cookie;

class UsersController extends AppController
{
    public function login()
    {
        $this->viewBuilder()->setLayout('auth');
        $this->request->allowMethod(['get', 'post']);
        
        $result = $this->Authentication->getResult();
        
        if ($result && $result->isValid()) {
            $target = $this->Authentication->getLoginRedirect() ?? '/dashboard';
            return $this->redirect($target);
        }
        
        if ($this->request->is('post')) {
            $this->Flash->error('Invalid username or password');
        }

        if ($this->request->getQuery('logged_out')) {
            $this->Flash->success('You have been logged out.');
        }
    }

    public function register()
    {
        $this->viewBuilder()->setLayout('auth');
        $this->request->allowMethod(['get', 'post']);
        
        $user = $this->Users->newEmptyEntity();
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            
            if (!empty($data['password']) && !empty($data['confirm_password'])) {
                if ($data['password'] !== $data['confirm_password']) {
                    $this->Flash->error('Passwords do not match');
                    $this->set(compact('user'));
                    return;
                }
            }
            
            if (!empty($data['password'])) {
                $data['password_hash'] = $data['password'];
            }

            unset($data['confirm_password'], $data['password']);

            $user = $this->Users->patchEntity($user, $data);
            
            if ($this->Users->save($user)) {
                $this->Flash->success('Account created successfully! Please login.');
                return $this->redirect(['action' => 'login']);
            }

            try {
                Log::warning('Register save failed, request data: ' . json_encode($this->request->getData()));
            } catch (\Throwable $e) {
    
            }

            // Expose raw POST data to the view for temporary debugging
            $this->set('rawPost', $this->request->getData());
            
            // Collect all validation errors and include field names for clarity
            $errors = $user->getErrors();
            $errorMessages = [];
            foreach ($errors as $field => $fieldErrors) {
                foreach ($fieldErrors as $errorKey => $error) {
                    $msg = is_array($error) ? implode(', ', $error) : $error;
                    // Use human-friendly field label when possible
                    $label = $field;
                    if ($field === 'password_hash') {
                        $label = 'password';
                    }
                    $errorMessages[] = sprintf('%s: %s', $label, $msg);
                }
            }

            if (!empty($errorMessages)) {
                $this->Flash->error(implode(' | ', $errorMessages));
            } else {
                $this->Flash->error('Unable to create account. Please try again.');
            }
        }
        
        $this->set(compact('user'));
    }

    public function logout()
    {
        $this->request->allowMethod(['post']);
        $logoutRedirect = null;

        // If the Authentication component is available, use it to logout the identity
        if (isset($this->Authentication)) {
            try {
                $logoutRedirect = $this->Authentication->logout();
            } catch (\Throwable $e) {
                // ignore - fallback to session destroy below
            }
        }

        // Ensure session is terminated (call destroy even if not started)
        try {
            $session = $this->request->getSession();
            $session->destroy();
        } catch (\Throwable $e) {
            // ignore session destroy errors
        }

        // Clear identity attribute on the current request to avoid accidental leakage
        try {
            if (method_exists($this, 'setRequest')) {
                $this->setRequest($this->getRequest()->withAttribute('identity', null));
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Expire PHP session cookie explicitly if present
        try {
            $cookieName = ini_get('session.name') ?: 'PHPSESSID';
            $expiredCookie = Cookie::create($cookieName, '')->withExpired();
            $this->response = $this->response->withCookie($expiredCookie);
        } catch (\Throwable $e) {
            // ignore
        }

        // Ensure all PHP session data is removed and cookie expired
        try {
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', ($params['secure'] ?? false), ($params['httponly'] ?? false));
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
                session_unset();
                session_destroy();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Redirect to login with a flag so the new session can show a logout message
        // Also explicitly send an expired Set-Cookie header to ensure browsers drop the session cookie
        try {
            $cookieName = session_name() ?: 'PHPSESSID';
            $expired = sprintf("%s=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; HttpOnly; SameSite=Lax", $cookieName);
            $this->response = $this->response->withHeader('Set-Cookie', $expired);
        } catch (\Throwable $e) {
            // ignore
        }

        if (!empty($logoutRedirect)) {
            return $this->redirect($logoutRedirect, 303);
        }

        return $this->redirect('/login?logged_out=1', 303);
    }

    public function dashboard()
    {
        // Use authenticated identity for dashboard data
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->redirect('/login');
        }

        $identity = $this->Authentication->getIdentity();
        
        // Get user ID from identity
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
        
        // Fetch fresh user data from database
        $userEntity = $this->Users->get($userId);
        $user = $userEntity->toArray();

        // Convert DateTime objects to ISO strings for client-side parsing
        foreach (['created', 'modified'] as $dtField) {
            if (!empty($user[$dtField]) && $user[$dtField] instanceof \DateTimeInterface) {
                $user[$dtField] = $user[$dtField]->format(DATE_ATOM);
            }
        }

        $this->set(compact('user'));
    }

    public function profile()
    {
        // Use authenticated identity for profile data
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->redirect('/login');
        }

        $identity = $this->Authentication->getIdentity();
        
        // Get user ID from identity
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
        
        // Fetch fresh user data from database
        $userEntity = $this->Users->get($userId);
        $user = $userEntity->toArray();

        // Convert DateTime objects to ISO strings for client-side parsing
        foreach (['created', 'modified'] as $dtField) {
            if (!empty($user[$dtField]) && $user[$dtField] instanceof \DateTimeInterface) {
                $user[$dtField] = $user[$dtField]->format(DATE_ATOM);
            }
        }

        $this->set(compact('user'));
    }

    public function updateProfile()
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
        $user = $this->Users->get($userId);
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
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]));
        }

        // Update user
        $user = $this->Users->patchEntity($user, $data);
        
        if ($this->Users->save($user)) {
            // Refresh the authentication session with updated user data
            $this->Authentication->setIdentity($user);
            
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
                    'message' => 'Profile updated successfully',
                    'user' => $responseData
                ]));
        } else {
            $validationErrors = $user->getErrors();
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Failed to update profile',
                    'errors' => $validationErrors
                ]));
        }
    }
}
