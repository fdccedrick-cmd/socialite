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
        // Ensure user is authenticated
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->redirect('/login');
        }

        // Get current user ID
        $userId = $this->getCurrentUserId();
        
        // Load user data (delegated to UsersTable)
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->getFormatted($userId);
        
        // Load posts with engagement data (delegated to PostsTable)
        $postsTable = $this->getTableLocator()->get('Posts');
        $postsArray = $postsTable->getPostsWithEngagement($userId);

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
