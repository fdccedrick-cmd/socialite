<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\ORM\Locator\LocatorAwareTrait;

class AppController extends Controller
{
    use LocatorAwareTrait;
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
    
        $identity = $this->Authentication->getIdentity();
        $currentUser = null;
        
        if ($identity) {
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
        
            if ($userId) {
                try {
                    $usersTable = $this->getTableLocator()->get('Users');
                    $currentUser = $usersTable->get($userId);
                } catch (\Exception $e) {
                    $currentUser = $identity;
                }
            }
        }
        
        $this->set('currentUser', $currentUser);
    }
    
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['login', 'register']);
    }
}
