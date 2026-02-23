<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSavedPosts extends BaseMigration
{
    /**
     * Create saved_posts table for users to save posts
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('saved_posts');
        
        $table->addColumn('user_id', 'integer', [
            'null' => false,
            'comment' => 'ID of user who saved the post'
        ])
        ->addColumn('post_id', 'biginteger', [
            'null' => false,
            'comment' => 'ID of the saved post'
        ])
        ->addColumn('created_at', 'datetime', [
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'When the post was saved'
        ])
        ->addIndex(['user_id'], ['name' => 'idx_saved_posts_user_id'])
        ->addIndex(['post_id'], ['name' => 'idx_saved_posts_post_id'])
        ->addIndex(['user_id', 'post_id'], [
            'unique' => true,
            'name' => 'unique_user_post'
        ])
        ->addForeignKey('user_id', 'users', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_saved_posts_user'
        ])
        ->addForeignKey('post_id', 'posts', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
            'constraint' => 'fk_saved_posts_post'
        ])
        ->create();
    }
}
