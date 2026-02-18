<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('users');
        $this->setDisplayField('full_name');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        // User has many notifications they receive
        $this->hasMany('Notifications', [
            'foreignKey' => 'user_id',
            'dependent' => true,
        ]);

        // User has many notifications they triggered (as actor)
        $this->hasMany('ActorNotifications', [
            'className' => 'Notifications',
            'foreignKey' => 'actor_id',
            'dependent' => true,
        ]);

        // User has many posts
        $this->hasMany('Posts', [
            'foreignKey' => 'user_id',
            'dependent' => true,
        ]);

        // User has many likes
        $this->hasMany('Likes', [
            'foreignKey' => 'user_id',
            'dependent' => true,
        ]);

        // User has many comments
        $this->hasMany('Comments', [
            'foreignKey' => 'user_id',
            'dependent' => true,
        ]);

        // User has many friendships (as initiator)
        $this->hasMany('Friendships', [
            'foreignKey' => 'user_id',
            'dependent' => true,
        ]);

        // User has many friendships (as receiver)
        $this->hasMany('ReceivedFriendships', [
            'className' => 'Friendships',
            'foreignKey' => 'friend_id',
            'dependent' => true,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('full_name')
            ->maxLength('full_name', 150)
            ->requirePresence('full_name', 'create')
            ->notEmptyString('full_name', 'Full name is required');

        $validator
            ->scalar('username')
            ->maxLength('username', 50)
            ->requirePresence('username', 'create')
            ->notEmptyString('username', 'Username is required')
            ->minLength('username', 3, 'Username must be at least 3 characters')
            ->alphaNumeric('username', 'Username can only contain letters and numbers');

        $validator
            ->scalar('password_hash')
            ->maxLength('password_hash', 255)
            ->requirePresence('password_hash', 'create')
            ->notEmptyString('password_hash', 'Password is required')
            ->minLength('password_hash', 6, 'Password must be at least 6 characters');

        $validator
            ->scalar('profile_photo_path')
            ->maxLength('profile_photo_path', 255)
            ->allowEmptyString('profile_photo_path');
        $validator
            ->scalar('bio')
            ->maxLength('bio', 500)
            ->allowEmptyString('bio');
        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['username'], 'This username is already taken'), ['errorField' => 'username']);
        return $rules;
    }

    /**
     * Get user data formatted for templates
     * 
     * @param int $userId
     * @return array
     */
    public function getFormatted($userId)
    {
        $userEntity = $this->get($userId);
        $user = $userEntity->toArray();

        foreach (['created', 'modified'] as $dtField) {
            if (!empty($user[$dtField]) && $user[$dtField] instanceof \DateTimeInterface) {
                $user[$dtField] = $user[$dtField]->format(DATE_ATOM);
            }
        }

        return $user;
    }
}
