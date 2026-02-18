<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateFriendships extends BaseMigration
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
        $table = $this->table('friendships');

        $table
            ->addColumn('user_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('friend_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'pending',
                'comment' => 'pending, accepted, rejected, blocked',
            ])
            ->addTimestamps() // created and modified
            ->addIndex(['user_id'])
            ->addIndex(['friend_id'])
            ->addIndex(['status'])
            ->addIndex(['user_id', 'friend_id'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('friend_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }
}
