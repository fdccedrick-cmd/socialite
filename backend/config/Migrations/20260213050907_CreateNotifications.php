<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateNotifications extends BaseMigration
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
        $table = $this->table('notifications');
        $table
            ->addColumn('user_id', 'integer', [
                'limit' => null,
                'null' => false,
                'comment' => 'User who receives the notification',
            ])
            ->addColumn('actor_id', 'integer', [
                'limit' => null,
                'null' => false,
                'comment' => 'User who triggered the notification',
            ])
            ->addColumn('type', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Type: like, comment, follow, mention, share, reply',
            ])
            ->addColumn('notifiable_type', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Model type: Post, Comment, User',
            ])
            ->addColumn('notifiable_id', 'integer', [
                'limit' => null,
                'null' => false,
                'comment' => 'ID of the notifiable item',
            ])
            ->addColumn('message', 'text', [
                'null' => false,
                'comment' => 'Notification message text',
            ])
            ->addColumn('is_read', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'Whether notification has been read',
            ])
            ->addColumn('read_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'When notification was read',
            ])
            ->addTimestamps()
            // Add indexes for performance
            ->addIndex(['user_id'], ['name' => 'idx_notifications_user_id'])
            ->addIndex(['actor_id'], ['name' => 'idx_notifications_actor_id'])
            ->addIndex(['is_read'], ['name' => 'idx_notifications_is_read'])
            ->addIndex(['user_id', 'is_read'], ['name' => 'idx_notifications_user_read'])
            ->addIndex(['notifiable_type', 'notifiable_id'], ['name' => 'idx_notifications_notifiable'])
            ->addIndex(['created'], ['name' => 'idx_notifications_created'])
            // Add foreign keys
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_notifications_user'
            ])
            ->addForeignKey('actor_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_notifications_actor'
            ])
            ->create();
    }
}
