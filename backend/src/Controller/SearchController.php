<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Search Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 * @property \App\Model\Table\PostsTable $Posts
 * @property \App\Model\Table\FriendshipsTable $Friendships
 */
class SearchController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
        
        $this->Users = $this->fetchTable('Users');
        $this->Posts = $this->fetchTable('Posts');
        $this->Friendships = $this->fetchTable('Friendships');
    }

    public function index()
    {
        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->redirect('/login');
        }

        $query = $this->request->getQuery('q', '');
        $query = trim($query);

        $userId = $this->getCurrentUserId();
        $users = [];
        $posts = [];


        if (!empty($query)) {
            try {
                // Search Users (prioritize friends)
                $users = $this->searchUsers($userId, $query);

                // Search Posts by caption AND by user
                $posts = $this->searchPosts($userId, $query);
            } catch (\Exception $e) {
                // Log the error but continue to display page
                error_log('Search error: ' . $e->getMessage());
                $this->Flash->error('An error occurred while searching. Please try again.');
            }
        }

        $this->set(compact('query', 'users', 'posts'));
    }

    /**
     * Quick search API endpoint for instant results
     */
    public function quick()
    {
        $this->request->allowMethod(['get']);
        $this->viewBuilder()->setClassName('Json');

        $result = $this->Authentication->getResult();
        if (!($result && $result->isValid())) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => 'Unauthorized']));
        }

        try {
            $query = $this->request->getQuery('q', '');
            $query = trim($query);

            if (empty($query)) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode(['success' => true, 'users' => [], 'posts' => []]));
            }

            $userId = $this->getCurrentUserId();

            // Get top 5 users (friends first)
            $users = array_slice($this->searchUsers($userId, $query), 0, 5);

            // Get top 3 posts
            $posts = array_slice($this->searchPosts($userId, $query), 0, 3);

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'users' => $users,
                    'posts' => $posts,
                ]));
        } catch (\Exception $e) {
            // Log the error
            error_log('Search error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return $this->response
                ->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'An error occurred while searching',
                    'error' => $e->getMessage()
                ]));
        }
    }

    private function searchUsers($currentUserId, $searchQuery)
    {
        // Get friend IDs for prioritization
        $friendships = $this->Friendships->find()
            ->where([
                'OR' => [
                    'user_id' => $currentUserId,
                    'friend_id' => $currentUserId,
                ],
                'status' => 'accepted',
            ])
            ->all();

        $friendIds = [];
        foreach ($friendships as $friendship) {
            if ($friendship->user_id == $currentUserId) {
                $friendIds[] = $friendship->friend_id;
            } else {
                $friendIds[] = $friendship->user_id;
            }
        }

        // Search all users matching the query
        $allUsers = $this->Users->find()
            ->where([
                'id !=' => $currentUserId,
                'OR' => [
                    'full_name LIKE' => '%' . $searchQuery . '%',
                    'username LIKE' => '%' . $searchQuery . '%',
                ],
            ])
            ->limit(50)
            ->all();

        // Separate friends and non-friends
        $friends = [];
        $nonFriends = [];

        foreach ($allUsers as $user) {
            $userData = [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'profile_photo_path' => $user->profile_photo_path,
                'bio' => $user->bio,
                'is_friend' => in_array($user->id, $friendIds),
            ];

            if (in_array($user->id, $friendIds)) {
                $friends[] = $userData;
            } else {
                $nonFriends[] = $userData;
            }
        }

        // Return friends first, then non-friends
        return array_merge($friends, $nonFriends);
    }

    private function searchPosts($currentUserId, $searchQuery)
    {
        $postsTable = $this->getTableLocator()->get('Posts');
        $likesTable = $this->getTableLocator()->get('Likes');
        $commentsTable = $this->getTableLocator()->get('Comments');
        $usersTable = $this->getTableLocator()->get('Users');
        
        // Search posts by content OR by user name/username
        $postsQuery = $postsTable->find()
            ->contain([
                'Users', 
                'PostImages' => ['sort' => ['PostImages.sort_order' => 'ASC']]
            ])
            ->leftJoinWith('Users')
            ->where([
                'OR' => [
                    'Posts.content_text LIKE' => '%' . $searchQuery . '%',
                    'Users.full_name LIKE' => '%' . $searchQuery . '%',
                    'Users.username LIKE' => '%' . $searchQuery . '%',
                ],
                'Posts.deleted IS' => null
            ])
            ->order(['Posts.created' => 'DESC'])
            ->limit(50);

        $posts = $postsQuery->all();

        $postsArray = [];
        foreach ($posts as $post) {
            // Skip if post has no user
            if (!$post->user) {
                continue;
            }
            
            $postData = $post->toArray();
            
            // Format dates
            if (!empty($postData['created']) && $postData['created'] instanceof \DateTimeInterface) {
                $postData['created'] = $postData['created']->format(DATE_ATOM);
            }
            if (!empty($postData['modified']) && $postData['modified'] instanceof \DateTimeInterface) {
                $postData['modified'] = $postData['modified']->format(DATE_ATOM);
            }
            
            // Add like data
            $postData['like_count'] = $likesTable->getLikeCount('Post', $post->id);
            $postData['is_liked'] = $likesTable->isLikedByUser('Post', $post->id, $currentUserId);
            
            // Add comments (full data structure for interactivity)
            $postData['comments'] = $commentsTable->getCommentsForPost($post->id, $currentUserId);
            $postData['comment_count'] = count($postData['comments']);
            
            $postsArray[] = $postData;
        }

        return $postsArray;
    }

    private function getCurrentUserId()
    {
        $identity = $this->Authentication->getIdentity();
        return $identity ? $identity->getIdentifier() : null;
    }
}
