<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class InsertRandomUsers extends BaseMigration
{
    /**
     * Change Method.
     *
     * Inserts 10 random users with predefined data.
     * Password for all users: admin123
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');
        
        // Hash the password 'admin123' for all users
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        
        // Use timestamp to ensure unique usernames
        $timestamp = time();
        $now = date('Y-m-d H:i:s');
        
        $users = [
            [
                'full_name' => 'Alex Johnson',
                'username' => 'alex_johnson_' . $timestamp . '1',
                'password_hash' => $passwordHash,
                'profile_photo_path' => null,
                'cover_photo_path' => null,
                'bio' => null,
                'theme' => 'dark',
                'address' => null,
                'relationship_status' => null,
                'contact_links' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'full_name' => 'Sam Martinez',
                'username' => 'sam_martinez_' . $timestamp . '2',
                'password_hash' => $passwordHash,
                'profile_photo_path' => null,
                'cover_photo_path' => null,
                'bio' => null,
                'theme' => 'dark',
                'address' => null,
                'relationship_status' => null,
                'contact_links' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'full_name' => 'Jordan Lee',
                'username' => 'jordan_lee_' . $timestamp . '3',
                'password_hash' => $passwordHash,
                'profile_photo_path' => null,
                'cover_photo_path' => null,
                'bio' => null,
                'theme' => 'dark',
                'address' => null,
                'relationship_status' => null,
                'contact_links' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'full_name' => 'Taylor Brown',
                'username' => 'taylor_brown_' . $timestamp . '4',
                'password_hash' => $passwordHash,
                'profile_photo_path' => null,
                'cover_photo_path' => null,
                'bio' => null,
                'theme' => 'dark',
                'address' => null,
                'relationship_status' => null,
                'contact_links' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'full_name' => 'Morgan Davis',
                'username' => 'morgan_davis_' . $timestamp . '5',
                'password_hash' => $passwordHash,
                'profile_photo_path' => null,
                'cover_photo_path' => null,
                'bio' => null,
                'theme' => 'dark',
                'address' => null,
                'relationship_status' => null,
                'contact_links' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'full_name' => 'Casey Wilson',
                'username' => 'casey_wilson_' . $timestamp . '6',
                'password_hash' => $passwordHash,
                'profile_photo_path' => null,
                'cover_photo_path' => null,
                'bio' => null,
                'theme' => 'dark',
                'address' => null,
                'relationship_status' => null,
                'contact_links' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'full_name' => 'Riley Anderson',
                'username' => 'riley_anderson_' . $timestamp . '7',
                'password_hash' => $passwordHash,
                'profile_photo_path' => null,
                'cover_photo_path' => null,
                'bio' => null,
                'theme' => 'dark',
                'address' => null,
                'relationship_status' => null,
                'contact_links' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'full_name' => 'Avery Thomas',
                'username' => 'avery_thomas_' . $timestamp . '8',
                'password_hash' => $passwordHash,
                'profile_photo_path' => null,
                'cover_photo_path' => null,
                'bio' => null,
                'theme' => 'dark',
                'address' => null,
                'relationship_status' => null,
                'contact_links' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'full_name' => 'Quinn Jackson',
                'username' => 'quinn_jackson_' . $timestamp . '9',
                'password_hash' => $passwordHash,
                'profile_photo_path' => null,
                'cover_photo_path' => null,
                'bio' => null,
                'theme' => 'dark',
                'address' => null,
                'relationship_status' => null,
                'contact_links' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'full_name' => 'Cameron White',
                'username' => 'cameron_white_' . $timestamp . '10',
                'password_hash' => $passwordHash,
                'profile_photo_path' => null,
                'cover_photo_path' => null,
                'bio' => null,
                'theme' => 'dark',
                'address' => null,
                'relationship_status' => null,
                'contact_links' => null,
                'created' => $now,
                'modified' => $now,
            ],
        ];
        
        $table->insert($users)->save();
    }
}
