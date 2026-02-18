<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Friendship extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'friend_id' => true,
        'status' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
        'friend' => true,
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_BLOCKED = 'blocked';
}
