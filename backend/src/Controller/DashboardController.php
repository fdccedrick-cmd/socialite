<?php
declare(strict_types=1);

namespace App\Controller;

class DashboardController extends AppController
{
    /**
     * Dashboard index - displays feed of posts
     * 
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->redirect('/login');
        }
        $userId = $this->getCurrentUserId();
        
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->getFormatted($userId);
        
        $postsTable = $this->getTableLocator()->get('Posts');
        $postsArray = $postsTable->getPostsWithEngagement($userId);

        // Friends and suggestions are now provided by AppController's beforeRender
        $this->set(compact('user', 'postsArray'));
    }

    /**
     * Helper method to extract user ID from authentication identity
     * 
     * @return int|null
     */
    protected function getCurrentUserId()
    {
        $identity = $this->Authentication->getIdentity();
        
        if (is_object($identity)) {
            if (method_exists($identity, 'getOriginalData')) {
                $orig = $identity->getOriginalData();
                if (is_object($orig) && isset($orig->id)) {
                    return $orig->id;
                } elseif (is_array($orig) && isset($orig['id'])) {
                    return $orig['id'];
                }
            } elseif (isset($identity->id)) {
                return $identity->id;
            }
        } elseif (is_array($identity) && isset($identity['id'])) {
            return $identity['id'];
        }
        
        return null;
    }
}
