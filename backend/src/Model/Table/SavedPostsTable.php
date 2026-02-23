<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SavedPosts Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\PostsTable&\Cake\ORM\Association\BelongsTo $Posts
 */
class SavedPostsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config Configuration options
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('saved_posts');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Posts', [
            'foreignKey' => 'post_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules
     *
     * @param \Cake\Validation\Validator $validator Validator instance
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->integer('post_id')
            ->requirePresence('post_id', 'create')
            ->notEmptyString('post_id');

        return $validator;
    }

    /**
     * Check if a user has saved a specific post
     *
     * @param int $userId User ID
     * @param int $postId Post ID
     * @return bool
     */
    public function isSaved(int $userId, int $postId): bool
    {
        return $this->exists([
            'SavedPosts.user_id' => $userId,
            'SavedPosts.post_id' => $postId,
        ]);
    }

    /**
     * Get all saved posts for a user
     *
     * @param int $userId User ID
     * @return \Cake\ORM\Query
     */
    public function getSavedPosts(int $userId)
    {
        return $this->find()
            ->where(['SavedPosts.user_id' => $userId])
            ->contain(['Posts' => [
                'Users',
                'PostImages' => ['sort' => ['PostImages.sort_order' => 'ASC']]
            ]])
            ->order(['SavedPosts.created_at' => 'DESC']);
    }
}
