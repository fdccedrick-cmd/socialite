<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateUsers extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');

        $table
            ->addColumn('full_name', 'string', [
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('username', 'string', [
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('password_hash', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('profile_photo_path', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addTimestamps() // created_at NOT NULL, updated_at NULL
            ->addIndex(['username'], ['unique' => true])
            ->create();
    }

}
