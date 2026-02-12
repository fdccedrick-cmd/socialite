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
        
        // If user is logged in, redirect to dashboard
        if ($result && $result->isValid()) {
            $target = $this->Authentication->getLoginRedirect() ?? '/dashboard';
            return $this->redirect($target);
        }
        
        // If it was a POST request but login failed
        if ($this->request->is('post')) {
            $this->Flash->error('Invalid username or password');
        }

        // Show logout success when redirected from logout (new session)
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

        // Normalize identity into an array suitable for JSON encoding in the view
        $user = [];
        if (is_object($identity)) {
            if (method_exists($identity, 'getOriginalData')) {
                $orig = $identity->getOriginalData();
                if (is_object($orig) && method_exists($orig, 'toArray')) {
                    $user = $orig->toArray();
                } elseif (is_array($orig)) {
                    $user = $orig;
                } else {
                    $user = (array)$orig;
                }
            } elseif (method_exists($identity, 'toArray')) {
                $user = $identity->toArray();
            } else {
                $user = (array)$identity;
            }
        } elseif (is_array($identity)) {
            $user = $identity;
        }

        // Convert DateTime objects to ISO strings for client-side parsing
        foreach (['created', 'modified'] as $dtField) {
            if (!empty($user[$dtField]) && $user[$dtField] instanceof \DateTimeInterface) {
                $user[$dtField] = $user[$dtField]->format(DATE_ATOM);
            }
        }

        $this->set(compact('user'));
    }
}
