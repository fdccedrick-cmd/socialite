<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateComments extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('comments', ['id' => false, 'primary_key' => ['id']]);
        
        $table->addColumn('id', 'biginteger', [
            'autoIncrement' => true,
            'default' => null,
            'limit' => null,
            'null' => false,
        ])
        ->addColumn('post_id', 'biginteger', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ])
        ->addColumn('user_id', 'integer', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ])
        ->addColumn('content_text', 'text', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ])
        ->addColumn('content_image_path', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => true,
        ])
        ->addColumn('created_at', 'datetime', [
            'default' => 'CURRENT_TIMESTAMP',
            'limit' => null,
            'null' => false,
        ])
        ->addColumn('updated_at', 'datetime', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ])
        ->addColumn('deleted_at', 'datetime', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ])
        ->addIndex(['post_id'])
        ->addIndex(['user_id'])
        ->addForeignKey('post_id', 'posts', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION'
        ])
        ->addForeignKey('user_id', 'users', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION'
        ])
        ->create();
    }
}
