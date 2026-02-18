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

    public function beforeRender(\Cake\Event\EventInterface $event)
    {
        parent::beforeRender($event);
        
        // Add friends and suggestions data for right sidebar
        $identity = $this->Authentication->getIdentity();
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
                    $friendshipsTable = $this->getTableLocator()->get('Friendships');
                    $usersTable = $this->getTableLocator()->get('Users');
                    
                    // Get friends for right sidebar
                    $friendships = $friendshipsTable->getFriends($userId);
                    
                    $friends = [];
                    foreach ($friendships as $friendship) {
                        // Determine which user is the friend
                        if ($friendship->user_id == $userId) {
                            $friend = $friendship->friend;
                            $friendId = $friendship->friend_id;
                        } else {
                            $friend = $friendship->user;
                            $friendId = $friendship->user_id;
                        }

                        $friends[] = [
                            'id' => $friendId,
                            'full_name' => $friend->full_name,
                            'username' => $friend->username,
                            'profile_photo_path' => $friend->profile_photo_path,
                        ];
                    }
                    
                    // Limit to 5 friends for sidebar
                    $friends = array_slice($friends, 0, 5);

                    // Get friend suggestions (only those with mutual friends)
                    $friendIds = [];
                    foreach ($friendships as $friendship) {
                        if ($friendship->user_id == $userId) {
                            $friendIds[] = $friendship->friend_id;
                        } else {
                            $friendIds[] = $friendship->user_id;
                        }
                    }

                    // Get all pending request IDs
                    $pendingIds = [];
                    $pendingRequests = $friendshipsTable->find()
                        ->select(['user_id', 'friend_id'])
                        ->where([
                            'OR' => [
                                'user_id' => $userId,
                                'friend_id' => $userId,
                            ],
                            'status' => 'pending',
                        ])
                        ->all();

                    foreach ($pendingRequests as $request) {
                        if ($request->user_id == $userId) {
                            $pendingIds[] = $request->friend_id;
                        } else {
                            $pendingIds[] = $request->user_id;
                        }
                    }

                    // Get users who are not friends and don't have pending requests
                    $excludeIds = array_merge($friendIds, $pendingIds, [$userId]);
                    
                    $potentialSuggestions = $usersTable->find()
                        ->where(['id NOT IN' => $excludeIds])
                        ->limit(50)
                        ->all();

                    $suggestions = [];
                    foreach ($potentialSuggestions as $user) {
                        // Get mutual friends count
                        $mutualCount = $friendshipsTable->getMutualFriendsCount($userId, $user->id);

                        // Only include if there are mutual friends
                        if ($mutualCount > 0) {
                            $suggestions[] = [
                                'id' => $user->id,
                                'full_name' => $user->full_name,
                                'username' => $user->username,
                                'profile_photo_path' => $user->profile_photo_path,
                                'mutual_friends_count' => $mutualCount,
                            ];
                        }
                    }

                    // Sort by mutual friends count (highest first) and limit to 5
                    usort($suggestions, function($a, $b) {
                        return $b['mutual_friends_count'] - $a['mutual_friends_count'];
                    });
                    $suggestions = array_slice($suggestions, 0, 5);

                    $this->set(compact('friends', 'suggestions'));
                } catch (\Exception $e) {
                    // Silently fail if tables don't exist or there's an error
                    $this->set('friends', []);
                    $this->set('suggestions', []);
                }
            }
        }
    }
}
