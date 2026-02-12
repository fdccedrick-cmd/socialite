<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;

class AppController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
        
        // Make current user available to all views
        $this->set('currentUser', $this->Authentication->getIdentity());
    }
    
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        
        // Allow unauthenticated access to login and register
        $this->Authentication->addUnauthenticatedActions(['login', 'register']);
    }
}
