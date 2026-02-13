<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateLikes extends BaseMigration
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
        $table = $this->table('likes');
        $table
            ->addColumn('user_id', 'integer', [
                'limit' => null,
                'null' => false,
                'comment' => 'User who liked',
            ])
            ->addColumn('target_type', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Type of target: Post or Comment',
            ])
            ->addColumn('target_id', 'integer', [
                'limit' => null,
                'null' => false,
                'comment' => 'ID of the post or comment',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'When the like was created',
            ])
            // Add indexes for performance
            ->addIndex(['user_id'], ['name' => 'idx_likes_user_id'])
            ->addIndex(['target_type', 'target_id'], ['name' => 'idx_likes_target'])
            // Unique composite constraint to prevent duplicate likes
            ->addIndex(['user_id', 'target_type', 'target_id'], [
                'unique' => true,
                'name' => 'unique_user_target_like'
            ])
            // Add foreign key for user
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_likes_user'
            ])
            ->create();
    }
}
