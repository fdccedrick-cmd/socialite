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

                // Search Posts by caption
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
        
        $postsQuery = $postsTable->find()
            ->contain(['Users', 'PostImages'])
            ->where([
                'Posts.content_text LIKE' => '%' . $searchQuery . '%',
            ])
            ->order(['Posts.created' => 'DESC'])
            ->limit(20);

        $posts = $postsQuery->all();

        $postsArray = [];
        foreach ($posts as $post) {
            // Skip if post has no user
            if (!$post->user) {
                continue;
            }
            
            $isLiked = $this->getTableLocator()->get('Likes')->exists([
                'target_type' => 'Post',
                'target_id' => $post->id,
                'user_id' => $currentUserId,
            ]);

            // Handle post_images - convert collection to array
            $postImages = [];
            if ($post->post_images) {
                foreach ($post->post_images as $image) {
                    $postImages[] = [
                        'id' => $image->id,
                        'image_path' => $image->image_path,
                    ];
                }
            }

            $postsArray[] = [
                'id' => $post->id,
                'content' => $post->content_text,
                'created' => $post->created ? $post->created->format('Y-m-d H:i:s') : null,
                'user_id' => $post->user_id,
                'user' => [
                    'id' => $post->user->id,
                    'full_name' => $post->user->full_name,
                    'username' => $post->user->username,
                    'profile_photo_path' => $post->user->profile_photo_path,
                ],
                'post_images' => $postImages,
                'like_count' => $this->getTableLocator()->get('Likes')->find()
                    ->where([
                        'target_type' => 'Post',
                        'target_id' => $post->id
                    ])
                    ->count(),
                'comment_count' => $this->getTableLocator()->get('Comments')->find()
                    ->where(['post_id' => $post->id])
                    ->count(),
                'is_liked' => $isLiked,
            ];
        }

        return $postsArray;
    }

    private function getCurrentUserId()
    {
        $identity = $this->Authentication->getIdentity();
        return $identity ? $identity->getIdentifier() : null;
    }
}
