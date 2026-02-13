<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Notification Entity
 *
 * @property int $id
 * @property int $user_id
 * @property int $actor_id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property string $message
 * @property bool $is_read
 * @property \Cake\I18n\DateTime|null $read_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\User $actor
 */
class Notification extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'actor_id' => true,
        'type' => true,
        'notifiable_type' => true,
        'notifiable_id' => true,
        'message' => true,
        'is_read' => true,
        'read_at' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
        'actor' => true,
    ];
}
