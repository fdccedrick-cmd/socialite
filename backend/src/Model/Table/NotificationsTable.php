<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Notifications Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Actors
 *
 * @method \App\Model\Entity\Notification newEmptyEntity()
 * @method \App\Model\Entity\Notification newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Notification> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Notification get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Notification findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Notification patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Notification> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Notification|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Notification saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Notification>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Notification>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Notification>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Notification> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Notification>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Notification>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Notification>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Notification> deleteManyOrFail(iterable $entities, array $options = [])
 */
class NotificationsTable extends Table
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

        $this->setTable('notifications');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        // Belongs to the user receiving the notification
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        // Belongs to the user who triggered the notification (actor)
        $this->belongsTo('Actors', [
            'className' => 'Users',
            'foreignKey' => 'actor_id',
            'joinType' => 'INNER',
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
            ->notEmptyString('user_id');

        $validator
            ->integer('actor_id')
            ->notEmptyString('actor_id');

        $validator
            ->scalar('type')
            ->maxLength('type', 50)
            ->requirePresence('type', 'create')
            ->notEmptyString('type')
            ->inList('type', ['like', 'comment', 'comment_like', 'follow', 'mention', 'share', 'reply', 'friend_request', 'friend_accept']);

        $validator
            ->scalar('notifiable_type')
            ->maxLength('notifiable_type', 50)
            ->requirePresence('notifiable_type', 'create')
            ->notEmptyString('notifiable_type')
            ->inList('notifiable_type', ['Post', 'Comment', 'User']);

        $validator
            ->integer('notifiable_id')
            ->requirePresence('notifiable_id', 'create')
            ->notEmptyString('notifiable_id');

        $validator
            ->scalar('message')
            ->requirePresence('message', 'create')
            ->notEmptyString('message');

        $validator
            ->boolean('is_read')
            ->notEmptyString('is_read');

        $validator
            ->dateTime('read_at')
            ->allowEmptyDateTime('read_at');

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
        $rules->add($rules->existsIn(['actor_id'], 'Actors'), ['errorField' => 'actor_id']);

        return $rules;
    }
}
