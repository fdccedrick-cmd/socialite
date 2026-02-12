<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Post extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'content_text' => true,
        'privacy' => true,
        'created' => true,
        'modified' => true,
        'deleted' => true,
        'user' => true,
        'post_images' => true,
    ];
}
