<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

class User extends Entity
{
    protected array $_accessible = [
        'id' => true,
        'full_name' => true,
        'username' => true,
        'password_hash' => true,
        'profile_photo_path' => true,
        'created' => true,
        'modified' => true,
        'bio' => true,
        'theme' => true,
    ];

    protected array $_hidden = [
        
        'password_hash',
    ];

    protected function _setPasswordHash(string $password): ?string
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher())->hash($password);
        }
        return null;
    }
}
