<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPostTypeToPostsTable extends BaseMigration
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
        $table = $this->table('posts');
        
        $table
            ->addColumn('post_type', 'enum', [
                'values' => ['regular', 'profile_photo', 'cover_photo'],
                'default' => 'regular',
                'null' => false,
                'after' => 'privacy'
            ])
            ->update();
    }
}
