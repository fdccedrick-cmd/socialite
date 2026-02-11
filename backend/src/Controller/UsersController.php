<?php
declare(strict_types=1);

namespace App\Controller;

class UsersController extends AppController
{
    public function login()
    {
        // Just render the template
        
    }

    public function register()
    {
        // Just render the template
        $user = null;
        $this->set(compact('user'));
    }

    public function logout()
    {
        $this->Flash->success('You have been logged out.');
        return $this->redirect(['action' => 'login']);
    }

    public function dashboard()
    {
        $user = null;
        $this->set(compact('user'));
    }
}
