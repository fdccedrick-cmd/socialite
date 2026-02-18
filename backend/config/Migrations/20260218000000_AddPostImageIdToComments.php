<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPostImageIdToComments extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('comments');
        $table
            ->addColumn('post_image_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(['post_image_id'])
            ->addForeignKey('post_image_id', 'post_images', 'id', [
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
                'constraint' => 'fk_comments_post_image'
            ])
            ->update();
    }
}
