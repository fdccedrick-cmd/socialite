<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class SeedPostsWithImages extends BaseMigration
{
    /**
     * Change Method.
     * 
     * Seeds 50 posts divided among existing users
     * Posts can have text, images, or both
     * Images are URLs from picsum.photos (1000x1000 minimum for scenery)
     */
    public function change(): void
    {
        $posts = $this->table('posts');
        $postImages = $this->table('post_images');
        
        // Dynamically fetch user IDs instead of hardcoding
        $userRows = $this->fetchAll('SELECT id FROM users ORDER BY id ASC LIMIT 10');
        $userIds = array_column($userRows, 'id');
        
        // Fallback if no users exist (shouldn't happen if migrations run in order)
        if (empty($userIds)) {
            return;
        }
        $postType = ['text', 'image', 'both'];
        
        // Sample post texts
        $postTexts = [
            "What an amazing view! Nature never ceases to amaze me. 🌄",
            "Stunning scenery today! Feeling blessed. ✨",
            "Just captured this beautiful moment. Love it! 📸",
            "The colors in this landscape are incredible! 🎨",
            "Nature's masterpiece right here. 🌅",
            "Breathtaking views everywhere I look! 😍",
            "This is why I love exploring new places! 🗺️",
            "Perfect weather for some outdoor adventures! ☀️",
            "Sometimes you just need to stop and appreciate the beauty around you. 💚",
            "Found this hidden gem today! 💎",
            "The mountains are calling and I must go! ⛰️",
            "Living for these peaceful moments. 🕊️",
            "Just another day in paradise! 🌴",
            "Sunset vibes hitting different today. 🌇",
            "Nature's canvas is the best art. 🎭",
            "Adventure awaits around every corner! 🧭",
            "Grateful for views like these. 🙏",
            "This place takes my breath away every time! 😊",
            "Finding beauty in every direction. 🌟",
            "The best views come after the hardest climbs. 💪",
            "Lost in the beauty of this moment. ✨",
            "Every sunset is an opportunity to reset. 🌅",
            "Nature is the best therapy. 🌿",
            "Chasing horizons and making memories! 📷",
            "This is what dreams are made of! ☁️",
            "Feeling small in the best way possible. 🌌",
            "The earth has music for those who listen. 🎵",
            "Wanderlust and wonder! 🌍",
            "Capturing moments that take my breath away. 💫",
            "The journey is the destination. 🛤️",
            "Finding peace in nature's embrace. 🤗",
            "Every view tells a story. 📖",
            "This is my happy place! 😄",
            "Nature always wears the colors of the spirit. 🌈",
            "Adventure is out there! 🚀",
            "Making memories one beautiful view at a time. 🎬",
            "The world is full of magic things. ✨",
            "Here's to the places that fill our souls! 💖",
            "Sometimes the most scenic roads in life are the detours. 🛣️",
            "Inhale the future, exhale the past. 🌬️"
        ];
        
        $now = date('Y-m-d H:i:s');
        $postsData = [];
        $postImagesData = [];
        
        // Generate 50 posts
        for ($i = 1; $i <= 50; $i++) {
            // Randomly select user
            $userId = $userIds[array_rand($userIds)];
            
            // Randomly select post type
            $type = $postType[array_rand($postType)];
            
            // Determine if post has text
            $contentText = null;
            if ($type === 'text' || $type === 'both') {
                $contentText = $postTexts[array_rand($postTexts)];
            }
            
            // Add post data
            $postsData[] = [
                'user_id' => $userId,
                'content_text' => $contentText,
                'privacy' => 'public',
                'created' => $now,
                'modified' => $now,
            ];
        }
        
        // Insert posts
        $posts->insert($postsData)->save();
        
        // Get the IDs of inserted posts
        $result = $this->fetchAll('SELECT id FROM posts ORDER BY id DESC LIMIT 50');
        $postIds = array_reverse(array_column($result, 'id'));
        
        // Now add images for posts that need them
        for ($i = 0; $i < 50; $i++) {
            $postId = $postIds[$i];
            $type = $postType[($i * 7) % 3]; // Pseudo-random distribution
            
            // If post has images
            if ($type === 'image' || $type === 'both') {
                // Randomly decide number of images (1-3)
                $numImages = rand(1, 3);
                
                for ($j = 0; $j < $numImages; $j++) {
                    // Generate random dimensions (minimum 1000x1000)
                    $width = rand(1000, 2048);
                    $height = rand(1000, 2048);
                    
                    // Use unique seed for each image (post_id * 100 + image_index)
                    // This ensures consistent images that don't change on reload
                    $seed = ($postId * 100) + $j;
                    
                    // Picsum photos URL - using /seed/ for consistent images based on seed
                    $imagePath = "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
                    
                    $postImagesData[] = [
                        'post_id' => $postId,
                        'image_path' => $imagePath,
                        'sort_order' => $j,
                        'created' => $now,
                    ];
                }
            }
        }
        
        // Insert post images if any
        if (!empty($postImagesData)) {
            $postImages->insert($postImagesData)->save();
        }
    }
}
