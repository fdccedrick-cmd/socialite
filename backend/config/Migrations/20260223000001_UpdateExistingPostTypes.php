<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class UpdateExistingPostTypes extends BaseMigration
{
    /**
     * Change Method.
     *
     * Updates existing posts to set their post_type based on content
     *
     * @return void
     */
    public function up(): void
    {
        // Update profile photo posts
        $this->execute("
            UPDATE posts 
            SET post_type = 'profile_photo' 
            WHERE content_text LIKE '%uploaded a new profile picture%'
            AND post_type = 'regular'
        ");
        
        // Update cover photo posts
        $this->execute("
            UPDATE posts 
            SET post_type = 'cover_photo' 
            WHERE content_text LIKE '%uploaded a new cover photo%'
            AND post_type = 'regular'
        ");
    }
    
    public function down(): void
    {
        // Revert all to regular
        $this->execute("
            UPDATE posts 
            SET post_type = 'regular' 
            WHERE post_type IN ('profile_photo', 'cover_photo')
        ");
    }
}
