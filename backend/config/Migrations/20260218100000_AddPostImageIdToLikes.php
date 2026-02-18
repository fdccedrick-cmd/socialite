<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPostImageIdToLikes extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('likes');
        $table
            ->addColumn('post_image_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'target_id',
                'comment' => 'ID of the specific post image being liked (optional, for multi-image posts)'
            ])
            ->addIndex(['post_image_id'], ['name' => 'idx_likes_post_image_id'])
            ->addForeignKey('post_image_id', 'post_images', 'id', [
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
                'constraint' => 'fk_likes_post_image'
            ])
            ->update();
    }
}
