<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class PostsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('posts');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        // Associations
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('PostImages', [
            'foreignKey' => 'post_id',
            'dependent' => true,
        ]);

        $this->hasMany('Comments', [
            'foreignKey' => 'post_id',
            'dependent' => true,
        ]);

        $this->hasMany('Likes', [
            'foreignKey' => 'post_id',
            'dependent' => true,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('content_text')
            ->allowEmptyString('content_text');

        $validator
            ->scalar('privacy')
            ->inList('privacy', ['public', 'friends', 'private'], 'Privacy must be public, friends, or private')
            ->notEmptyString('privacy');

        $validator
            ->dateTime('deleted')
            ->allowEmptyDateTime('deleted');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'), [
            'errorField' => 'user_id',
            'message' => 'The specified user does not exist'
        ]);

        return $rules;
    }

    /**
     * Get posts with engagement data for feed
     * 
     * @param int $userId
     * @return array
     */
    public function getPostsWithEngagement(int $userId): array
    {
        $likesTable = TableRegistry::getTableLocator()->get('Likes');
        $commentsTable = TableRegistry::getTableLocator()->get('Comments');
        
        $posts = $this->find()
            ->where(['Posts.deleted IS' => null])
            ->contain([
                'Users',
                'PostImages' => ['sort' => ['PostImages.sort_order' => 'ASC']]
            ])
            ->order(['Posts.created' => 'DESC'])
            ->toArray();
        
        $postsArray = [];
        foreach ($posts as $post) {
            $postData = $post->toArray();
            
            // Format dates
            if (!empty($postData['created']) && $postData['created'] instanceof \DateTimeInterface) {
                $postData['created'] = $postData['created']->format(DATE_ATOM);
            }
            if (!empty($postData['modified']) && $postData['modified'] instanceof \DateTimeInterface) {
                $postData['modified'] = $postData['modified']->format(DATE_ATOM);
            }
            
            // Add like data (delegated to LikesTable)
            $postData['like_count'] = $likesTable->getLikeCount('Post', $post->id);
            $postData['is_liked'] = $likesTable->isLikedByUser('Post', $post->id, $userId);
            
            // Add comments (delegated to CommentsTable)
            $postData['comments'] = $commentsTable->getCommentsForPost($post->id, $userId);
            $postData['comment_count'] = count($postData['comments']);
            
            $postsArray[] = $postData;
        }
        
        return $postsArray;
    }
      
}
