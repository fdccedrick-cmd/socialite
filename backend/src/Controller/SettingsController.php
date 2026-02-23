<?php
declare(strict_types=1);

namespace App\Controller;

use Authentication\PasswordHasher\DefaultPasswordHasher;

class SettingsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        error_log('SettingsController::initialize() called');
    }
    
    public function index()
    {
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->redirect('/login');
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

        $usersTable = $this->getTableLocator()->get('Users');
        $user = null;
        if ($userId) {
            $user = $usersTable->get($userId)->toArray();
        }

        if ($this->request->getQuery('updated')) {
            $this->Flash->success('Password updated successfully!');
            return $this->redirect(['controller' => 'Settings', 'action' => 'index']);
        }

        // Get the section from query string, default to 'account'
        $section = $this->request->getQuery('section', 'account');
        if (!in_array($section, ['account', 'theme'])) {
            $section = 'account';
        }

        $this->set(compact('user', 'section'));
    }

    public function account()
    {
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->redirect('/login');
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

        $usersTable = $this->getTableLocator()->get('Users');
        $user = null;
        if ($userId) {
            $user = $usersTable->get($userId)->toArray();
        }

        // If redirected here after a successful password update, set Flash then redirect
        if ($this->request->getQuery('updated')) {
            $this->Flash->success('Password updated successfully!');
            return $this->redirect(['controller' => 'Settings', 'action' => 'account']);
        }

        $this->set(compact('user'));
    }

    public function updatePassword()
    {
        error_log('=== updatePassword called ===');
        
        try {
            $this->request->allowMethod(['post']);
            $this->viewBuilder()->disableAutoLayout();

            $result = $this->Authentication->getResult();
            if (!($result && $result->isValid())) {
                error_log('updatePassword: Authentication failed');
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
                error_log('updatePassword: User ID not found');
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => 'User not found'
                    ]));
            }

            error_log('updatePassword: User ID: ' . $userId);
            $usersTable = $this->getTableLocator()->get('Users');
            $user = $usersTable->get($userId);
            error_log('updatePassword: User loaded successfully');

            $currentPassword = $this->request->getData('current_password');
            $newPassword = $this->request->getData('new_password');
            error_log('updatePassword: Received current_password: ' . ($currentPassword ? 'yes' : 'no'));
            error_log('updatePassword: Received new_password: ' . ($newPassword ? 'yes' : 'no'));

            $errors = [];

            if (empty($currentPassword)) {
                $errors['current_password'] = 'Current password is required to change password.';
            }
            if (empty($newPassword)) {
                $errors['new_password'] = 'New password is required.';
            } elseif (strlen($newPassword) < 6) {
                $errors['new_password'] = 'Password must be at least 6 characters.';
            }

            if (empty($errors)) {
                error_log('updatePassword: No validation errors, checking current password');
                $hasher = new DefaultPasswordHasher();
                if (!$hasher->check($currentPassword, $user->password_hash)) {
                    error_log('updatePassword: Current password check failed');
                    $errors['current_password'] = 'Current password is incorrect.';
                } else {
                    error_log('updatePassword: Current password verified, setting new password');
                    $user->password_hash = $newPassword;
                }
            }

            if (!empty($errors)) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => false,
                        'errors' => $errors
                    ]));
            }

            error_log('updatePassword: Attempting to save user');
            $user = $usersTable->patchEntity($user, ['password_hash' => $user->password_hash]);
            if ($usersTable->save($user)) {
                error_log('updatePassword: Password updated successfully');
                $this->Authentication->setIdentity($user);
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode(['success' => true]));
            }

            error_log('updatePassword: Failed to save user');
            $errors = $user->getErrors();
            error_log('updatePassword: Validation errors: ' . json_encode($errors));
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode(['success' => false, 'message' => 'Failed to update password', 'errors' => $errors]));
        } catch (\Exception $e) {
            error_log('updatePassword: Exception caught - ' . $e->getMessage());
            error_log('updatePassword: Stack trace - ' . $e->getTraceAsString());
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'An error occurred while updating password'
                ]));
        }
    }

    public function updateTheme()
    {
        error_log('=== updateTheme called ===');
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->disableAutoLayout();

        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            error_log('Authentication failed');
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
            error_log('User ID not found');
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]));
        }

        $theme = $this->request->getData('theme');
        error_log("User ID: $userId, Requested theme: $theme");
        
        // Validate theme value
        if (!in_array($theme, ['light', 'dark'])) {
            error_log('Invalid theme value');
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Invalid theme value'
                ]));
        }

        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get($userId);
        $user->theme = $theme;
        error_log("Before save - User {$user->id} theme: {$user->theme}");

        if ($usersTable->save($user)) {
            error_log("Theme saved successfully for user {$user->id}");
            // Update session identity with new theme
            $this->Authentication->setIdentity($user);
            
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Theme preference saved'
                ]));
        }

        // Log validation errors if any
        $errors = $user->getErrors();
        error_log("Failed to save theme for user {$user->id}. Errors: " . json_encode($errors));
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'message' => 'Failed to save theme preference'
            ]));
    }
}
