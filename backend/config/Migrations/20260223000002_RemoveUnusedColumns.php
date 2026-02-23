<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveUnusedColumns extends BaseMigration
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
        // Drop foreign key constraint on shared_post_id first
        $this->execute('ALTER TABLE posts DROP FOREIGN KEY posts_ibfk_1');
        
        // Remove shared_post_id and share_count from posts table
        $posts = $this->table('posts');
        $posts->removeColumn('shared_post_id')
              ->removeColumn('share_count')
              ->update();
        
        // Drop foreign key constraint on parent_comment_id first
        $this->execute('ALTER TABLE comments DROP FOREIGN KEY comments_ibfk_3');
        
        // Remove parent_comment_id from comments table
        $comments = $this->table('comments');
        $comments->removeColumn('parent_comment_id')
                 ->update();
    }
}
