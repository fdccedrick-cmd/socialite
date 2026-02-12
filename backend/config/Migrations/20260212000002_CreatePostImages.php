<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePostImages extends BaseMigration
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
        $table = $this->table('post_images', ['id' => false, 'primary_key' => ['id']]);

        $table
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('post_id', 'biginteger', [
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('image_path', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('sort_order', 'integer', [
                'limit' => null,
                'null' => true,
                'default' => 0,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['post_id'], ['name' => 'idx_post_images_post_id'])
            ->addForeignKey('post_id', 'posts', 'id', [
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
                'constraint' => 'fk_post_images_post'
            ])
            ->create();
    }
}
