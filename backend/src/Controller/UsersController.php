<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Log\Log;
use Cake\Http\Cookie\Cookie;
use Cake\ORM\Locator\LocatorAwareTrait;

class UsersController extends AppController
{
    public function login()
    {
        $this->viewBuilder()->setLayout('auth');
        $this->request->allowMethod(['get', 'post']);
    
        if ($this->request->is('post')) {
            error_log('LOGIN ATTEMPT - POST data: ' . json_encode($this->request->getData()));
            error_log('LOGIN ATTEMPT - $_POST: ' . json_encode($_POST));
        }
        
        $result = $this->Authentication->getResult();
        
        if ($result && $result->isValid()) {
            $target = $this->Authentication->getLoginRedirect() ?? '/dashboard';
            return $this->redirect($target);
        }
        
        if ($this->request->is('post')) {
            error_log('LOGIN FAILED - Auth result: ' . json_encode($result));
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

            $this->set('rawPost', $this->request->getData());
            
            $errors = $user->getErrors();
            $errorMessages = [];
            foreach ($errors as $field => $fieldErrors) {
                foreach ($fieldErrors as $errorKey => $error) {
                    $msg = is_array($error) ? implode(', ', $error) : $error;
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

        if (isset($this->Authentication)) {
            try {
                $logoutRedirect = $this->Authentication->logout();
            } catch (\Throwable $e) {
               
            }
        }

        try {
            $session = $this->request->getSession();
            $session->destroy();
        } catch (\Throwable $e) {
            
        }

        try {
            if (method_exists($this, 'setRequest')) {
                $this->setRequest($this->getRequest()->withAttribute('identity', null));
            }
        } catch (\Throwable $e) {
            
        }

        try {
            $cookieName = ini_get('session.name') ?: 'PHPSESSID';
            $expiredCookie = Cookie::create($cookieName, '')->withExpired();
            $this->response = $this->response->withCookie($expiredCookie);
        } catch (\Throwable $e) {
        
        }
        try {
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', ($params['secure'] ?? false), ($params['httponly'] ?? false));
            }
        } catch (\Throwable $e) {
          
        }

        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
                session_unset();
                session_destroy();
            }
        } catch (\Throwable $e) {
        
        }
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
}
