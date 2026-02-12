<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Log\Log;

class UsersController extends AppController
{
    public function login()
    {
        $this->request->allowMethod(['get', 'post']);
        
        $result = $this->Authentication->getResult();
        
        // If user is logged in, redirect to dashboard
        if ($result && $result->isValid()) {
            $target = $this->Authentication->getLoginRedirect() ?? '/dashboard';
            return $this->redirect($target);
        }
        
        // If it was a POST request but login failed
        if ($this->request->is('post')) {
            $this->Flash->error('Invalid username or password');
        }
    }

    public function register()
    {
        $this->request->allowMethod(['get', 'post']);
        
        $user = $this->Users->newEmptyEntity();
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            
            // Check if passwords match (before hashing)
            if (!empty($data['password']) && !empty($data['confirm_password'])) {
                if ($data['password'] !== $data['confirm_password']) {
                    $this->Flash->error('Passwords do not match');
                    $this->set(compact('user'));
                    return;
                }
            }
            
            // Map submitted plaintext password into the DB field expected by the table
            if (!empty($data['password'])) {
                $data['password_hash'] = $data['password'];
            }

            // Remove confirm_password and plaintext password from data before saving
            unset($data['confirm_password'], $data['password']);

            $user = $this->Users->patchEntity($user, $data);
            
            if ($this->Users->save($user)) {
                $this->Flash->success('Account created successfully! Please login.');
                return $this->redirect(['action' => 'login']);
            }

            // Log incoming POST data when save fails to help debug client-side issues
            try {
                Log::warning('Register save failed, request data: ' . json_encode($this->request->getData()));
            } catch (\Throwable $e) {
                // ignore logging errors
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
        $this->Flash->success('You have been logged out.');
        return $this->redirect(['action' => 'login']);
    }

    public function dashboard()
    {
        // Temporarily simplified
        $user = $this->Users->newEmptyEntity();
        $user->username = 'Test User';
        $user->full_name = 'Test User Name';
        $user->created = new \DateTime();
        $user->modified = new \DateTime();
        
        $this->set(compact('user'));
    }
}
