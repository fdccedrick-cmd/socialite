<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FriendshipsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('friendships');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        // User who sent the friend request
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        // User who received the friend request
        $this->belongsTo('Friends', [
            'className' => 'Users',
            'foreignKey' => 'friend_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->integer('friend_id')
            ->requirePresence('friend_id', 'create')
            ->notEmptyString('friend_id');

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->requirePresence('status', 'create')
            ->notEmptyString('status')
            ->inList('status', ['pending', 'accepted', 'rejected', 'blocked'], 'Invalid status value');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['user_id'], 'Users', 'Invalid user'));
        $rules->add($rules->existsIn(['friend_id'], 'Friends', 'Invalid friend'));
        
        // Prevent self-friending
        $rules->add(
            function ($entity, $options) {
                return $entity->user_id !== $entity->friend_id;
            },
            'noSelfFriend',
            [
                'errorField' => 'friend_id',
                'message' => 'You cannot add yourself as a friend',
            ]
        );

        return $rules;
    }

    /**
     * Check if two users are friends (accepted status)
     */
    public function areFriends(int $userId, int $friendId): bool
    {
        return $this->exists([
            'OR' => [
                [
                    'user_id' => $userId,
                    'friend_id' => $friendId,
                    'status' => 'accepted',
                ],
                [
                    'user_id' => $friendId,
                    'friend_id' => $userId,
                    'status' => 'accepted',
                ],
            ],
        ]);
    }

    /**
     * Get friendship status between two users
     */
    public function getFriendshipStatus(int $userId, int $friendId)
    {
        return $this->find()
            ->where([
                'OR' => [
                    [
                        'user_id' => $userId,
                        'friend_id' => $friendId,
                    ],
                    [
                        'user_id' => $friendId,
                        'friend_id' => $userId,
                    ],
                ],
            ])
            ->first();
    }

    /**
     * Get all friends for a user (accepted friendships)
     */
    public function getFriends(int $userId)
    {
        // Get friendships where user is either sender or receiver and status is accepted
        $friendships = $this->find()
            ->contain(['Users', 'Friends'])
            ->where([
                'OR' => [
                    'user_id' => $userId,
                    'friend_id' => $userId,
                ],
                'status' => 'accepted',
            ])
            ->all();

        return $friendships;
    }

    /**
     * Get pending friend requests for a user (requests they received)
     */
    public function getPendingRequests(int $userId)
    {
        return $this->find()
            ->contain(['Users'])
            ->where([
                'friend_id' => $userId,
                'status' => 'pending',
            ])
            ->all();
    }

    /**
     * Get sent friend requests (requests user sent that are still pending)
     */
    public function getSentRequests(int $userId)
    {
        return $this->find()
            ->contain(['Friends'])
            ->where([
                'user_id' => $userId,
                'status' => 'pending',
            ])
            ->all();
    }

    /**
     * Get mutual friends count between two users
     */
    public function getMutualFriendsCount(int $userId, int $friendId): int
    {
        $userFriends = $this->getFriendIds($userId);
        $friendFriends = $this->getFriendIds($friendId);

        return count(array_intersect($userFriends, $friendFriends));
    }

    /**
     * Get array of friend IDs for a user
     */
    private function getFriendIds(int $userId): array
    {
        $friendships = $this->find()
            ->select(['user_id', 'friend_id'])
            ->where([
                'OR' => [
                    'user_id' => $userId,
                    'friend_id' => $userId,
                ],
                'status' => 'accepted',
            ])
            ->all();

        $friendIds = [];
        foreach ($friendships as $friendship) {
            if ($friendship->user_id == $userId) {
                $friendIds[] = $friendship->friend_id;
            } else {
                $friendIds[] = $friendship->user_id;
            }
        }

        return $friendIds;
    }
}
