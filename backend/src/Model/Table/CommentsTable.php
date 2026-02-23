<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Comments Model
 *
 * @property \App\Model\Table\PostsTable&\Cake\ORM\Association\BelongsTo $Posts
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\Comment newEmptyEntity()
 * @method \App\Model\Entity\Comment newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Comment[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Comment get($primaryKey, $options = [])
 * @method \App\Model\Entity\Comment findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Comment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Comment[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Comment|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Comment saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class CommentsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('comments');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        // Associations
        $this->belongsTo('Posts', [
            'foreignKey' => 'post_id',
            'joinType' => 'INNER',
        ]);
        
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('PostImages', [
            'foreignKey' => 'post_image_id',
            'joinType' => 'LEFT',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->nonNegativeInteger('post_id')
            ->requirePresence('post_id', 'create')
            ->notEmptyString('post_id');

        $validator
            ->nonNegativeInteger('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('content_text')
            ->allowEmptyString('content_text');

        $validator
            ->scalar('content_image_path')
            ->maxLength('content_image_path', 255)
            ->allowEmptyString('content_image_path');

        $validator
            ->integer('post_image_id')
            ->allowEmptyString('post_image_id');

        // At least one of content_text or content_image_path must be present
        $validator
            ->add('content_text', 'custom', [
                'rule' => function ($value, $context) {
                    return !empty($value) || !empty($context['data']['content_image_path']);
                },
                'message' => 'Either content_text or content_image_path must be provided.'
            ]);

        $validator
            ->dateTime('created_at')
            ->notEmptyDateTime('created_at');

        $validator
            ->dateTime('updated_at')
            ->allowEmptyDateTime('updated_at');

        $validator
            ->dateTime('deleted_at')
            ->allowEmptyDateTime('deleted_at');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('post_id', 'Posts'), ['errorField' => 'post_id']);
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    /**
     * Find comments that are not soft deleted
     *
     * @param \Cake\ORM\Query $query
     * @param array $options
     * @return \Cake\ORM\Query
     */
    public function findActive(Query $query, array $options): Query
    {
        return $query->where(['Comments.deleted_at IS' => null]);
    }

    /**
     * Soft delete a comment
     *
     * @param int $id Comment ID
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        $comment = $this->get($id);
        $comment->deleted_at = new \DateTime();
        return (bool)$this->save($comment);
    }

    /**
     * Get comments for a post with like data
     * 
     * @param int $postId
     * @param int|null $userId Current user ID
     * @return array
     */
    public function getCommentsForPost(int $postId, ?int $userId): array
    {
        $likesTable = TableRegistry::getTableLocator()->get('Likes');
        
        $comments = $this->find()
            ->where([
                'post_id' => $postId,
                'deleted_at IS' => null,
                'post_image_id IS' => null  // Exclude image-specific comments
            ])
            ->contain(['Users' => function ($q) {
                return $q->select([
                    'id',
                    'username',
                    'full_name',
                    'profile_photo_path',
                    'created',
                    'modified'
                ]);
            }])
            ->order(['Comments.created_at' => 'ASC'])
            ->toArray();
        
        $commentsArray = [];
        foreach ($comments as $comment) {
            $commentData = $comment->toArray();
            
            // Ensure comment user data has avatar field for JavaScript compatibility
            if (!empty($commentData['user'])) {
                $commentData['user']['avatar'] = $commentData['user']['profile_photo_path'] ?? '/img/default/default_avatar.jpg';
            }
            
            // Format comment dates
            if (!empty($commentData['created_at']) && $commentData['created_at'] instanceof \DateTimeInterface) {
                $commentData['created_at'] = $commentData['created_at']->format(DATE_ATOM);
            }
            
            // Add like data (delegated to LikesTable)
            $commentData['like_count'] = $likesTable->getLikeCount('Comment', $comment->id);
            $commentData['is_liked'] = $likesTable->isLikedByUser('Comment', $comment->id, $userId);
            
            $commentsArray[] = $commentData;
        }
        
        return $commentsArray;
    }
}
