<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Like Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $target_type
 * @property int $target_id
 * @property int|null $post_image_id
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\PostImage|null $post_image
 */
class Like extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'target_type' => true,
        'target_id' => true,
        'post_image_id' => true,
        'created' => true,
        'user' => true,
        'post_image' => true,
    ];
}
