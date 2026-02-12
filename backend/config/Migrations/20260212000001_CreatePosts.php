<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePosts extends BaseMigration
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
        $table = $this->table('posts', ['id' => false, 'primary_key' => ['id']]);

        $table
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('content_text', 'text', [
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('privacy', 'enum', [
                'values' => ['public', 'friends', 'private'],
                'default' => 'public',
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->addColumn('deleted', 'datetime', [
                'null' => true,
            ])
            ->addIndex(['user_id'], ['name' => 'idx_posts_user_id'])
            ->addForeignKey('user_id', 'users', 'id', [
                'update' => 'NO_ACTION',
                'delete' => 'CASCADE',
                'constraint' => 'fk_posts_user'
            ])
            ->create();
    }
}
