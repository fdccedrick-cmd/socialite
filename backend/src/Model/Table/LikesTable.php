<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Likes Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\Like newEmptyEntity()
 * @method \App\Model\Entity\Like newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Like> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Like get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Like findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Like patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Like> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Like|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Like saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Like>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Like>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Like>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Like> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Like>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Like>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Like>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Like> deleteManyOrFail(iterable $entities, array $options = [])
 */
class LikesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('likes');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        // Belongs to user who liked
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        
        // Optional: belongs to post image (for likes on individual images in multi-image posts)
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
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('target_type')
            ->maxLength('target_type', 50)
            ->requirePresence('target_type', 'create')
            ->notEmptyString('target_type')
            ->inList('target_type', ['Post', 'Comment']);

        $validator
            ->integer('target_id')
            ->requirePresence('target_id', 'create')
            ->notEmptyString('target_id');

        $validator
            ->integer('post_image_id')
            ->allowEmptyString('post_image_id');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    /**
     * Get like count for a target
     * 
     * @param string $targetType 'Post' or 'Comment'
     * @param int $targetId
     * @return int
     */
    public function getLikeCount(string $targetType, int $targetId): int
    {
        $conditions = [
            'target_type' => $targetType,
            'target_id' => $targetId
        ];
        
        // For posts, only count general post likes (not image-specific)
        if ($targetType === 'Post') {
            $conditions['post_image_id IS'] = null;
        }
        
        return $this->find()
            ->where($conditions)
            ->count();
    }

    /**
     * Check if user liked a target
     * 
     * @param string $targetType 'Post' or 'Comment'
     * @param int $targetId
     * @param int|null $userId
     * @return bool
     */
    public function isLikedByUser(string $targetType, int $targetId, ?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        $conditions = [
            'target_type' => $targetType,
            'target_id' => $targetId,
            'user_id' => $userId
        ];
        
        // For posts, only check general post likes (not image-specific)
        if ($targetType === 'Post') {
            $conditions['post_image_id IS'] = null;
        }

        return $this->find()
            ->where($conditions)
            ->count() > 0;
    }
}
