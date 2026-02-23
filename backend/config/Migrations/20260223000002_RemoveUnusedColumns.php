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
        // Drop foreign key constraint on shared_post_id if it exists
        $this->execute("
            SET @constraint_name = (
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'posts' 
                AND COLUMN_NAME = 'shared_post_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            );
            SET @sql = IF(@constraint_name IS NOT NULL, 
                CONCAT('ALTER TABLE posts DROP FOREIGN KEY ', @constraint_name), 
                'SELECT 1'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        // Remove shared_post_id and share_count from posts table
        $posts = $this->table('posts');
        $posts->removeColumn('shared_post_id')
              ->removeColumn('share_count')
              ->update();
        
        // Drop foreign key constraint on parent_comment_id if it exists
        $this->execute("
            SET @constraint_name = (
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'comments' 
                AND COLUMN_NAME = 'parent_comment_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            );
            SET @sql = IF(@constraint_name IS NOT NULL, 
                CONCAT('ALTER TABLE comments DROP FOREIGN KEY ', @constraint_name), 
                'SELECT 1'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
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
            ADD CONSTRAINT posts_shared_post_fk 
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
            ADD CONSTRAINT comments_parent_comment_fk 
            FOREIGN KEY (parent_comment_id) REFERENCES comments(id) ON DELETE CASCADE
        ');
    }
}
