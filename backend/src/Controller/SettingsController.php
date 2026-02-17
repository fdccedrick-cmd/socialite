<?php
declare(strict_types=1);

namespace App\Controller;

use Authentication\PasswordHasher\DefaultPasswordHasher;

class SettingsController extends AppController
{
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

        $currentPassword = $this->request->getData('current_password');
        $newPassword = $this->request->getData('new_password');

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
            $hasher = new DefaultPasswordHasher();
            if (!$hasher->check($currentPassword, $user->password_hash)) {
                $errors['current_password'] = 'Current password is incorrect.';
            } else {
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

        $user = $usersTable->patchEntity($user, ['password_hash' => $user->password_hash]);
        if ($usersTable->save($user)) {
            $this->Authentication->setIdentity($user);
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode(['success' => true]));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['success' => false, 'message' => 'Failed to update password']));
    }
}
