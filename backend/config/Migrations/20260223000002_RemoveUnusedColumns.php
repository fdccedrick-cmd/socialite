<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveUnusedColumns extends BaseMigration
{
    /**
     * Up Method - Remove unused columns
     *
     * @return void
     */
    public function up(): void
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
    
    /**
     * Down Method - Restore the removed columns
     *
     * @return void
     */
    public function down(): void
    {
        // Restore shared_post_id and share_count to posts table
        $posts = $this->table('posts');
        $posts->addColumn('shared_post_id', 'biginteger', [
                'null' => true,
                'default' => null,
                'after' => 'user_id'
            ])
            ->addColumn('share_count', 'integer', [
                'null' => false,
                'default' => 0,
                'after' => 'content_text'
            ])
            ->addIndex('shared_post_id', ['name' => 'idx_posts_shared_post_id'])
            ->update();
        
        // Restore foreign key constraint on shared_post_id
        $this->execute('
            ALTER TABLE posts 
            ADD CONSTRAINT posts_ibfk_1 
            FOREIGN KEY (shared_post_id) REFERENCES posts(id) ON DELETE CASCADE
        ');
        
        // Restore parent_comment_id to comments table
        $comments = $this->table('comments');
        $comments->addColumn('parent_comment_id', 'biginteger', [
                'null' => true,
                'default' => null,
                'after' => 'post_image_id'
            ])
            ->addIndex('parent_comment_id', ['name' => 'idx_parent_comment_id'])
            ->update();
        
        // Restore foreign key constraint on parent_comment_id
        $this->execute('
            ALTER TABLE comments 
            ADD CONSTRAINT comments_ibfk_3 
            FOREIGN KEY (parent_comment_id) REFERENCES comments(id) ON DELETE CASCADE
        ');
    }
}
