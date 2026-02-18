<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPostImageIdToNotifications extends BaseMigration
{
    /**
     * Change Method.
     *
     * Add post_image_id column to notifications table to track which specific
     * image was liked (if applicable)
     */
    public function change(): void
    {
        $table = $this->table('notifications');
        $table
            ->addColumn('post_image_id', 'biginteger', [
                'null' => true,
                'default' => null,
                'after' => 'notifiable_id',
                'comment' => 'ID of post_image if notification is for an image like',
            ])
            ->addIndex(['post_image_id'], ['name' => 'idx_notifications_post_image_id'])
            ->addForeignKey('post_image_id', 'post_images', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_notifications_post_image'
            ])
            ->update();
    }
}
