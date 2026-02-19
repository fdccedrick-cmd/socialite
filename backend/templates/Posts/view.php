<?php
/**
 * Single Post view — reuse dashboard-style Vue hydration but load profile.js
 * so single-post pages get the same interactive behavior as profile (edit/delete).
 *
 * Variables:
 *  - $post (entity) or $postArray (array)
 *  - $currentUser
 */

$currentUser = $currentUser ?? ($this->Identity->get() ?? null);

// Convert entity to array for JSON encoding
if (!isset($postArray)) {
      $postArray = (is_object($post) && method_exists($post, 'toArray')) ? $post->toArray() : (array)$post;
}

// Ensure fields expected by the frontend
$postArray['is_liked'] = $postArray['is_liked'] ?? false;
$postArray['like_count'] = $postArray['like_count'] ?? 0;
$postArray['comments'] = $postArray['comments'] ?? [];
$postArray['comment_count'] = $postArray['comment_count'] ?? count($postArray['comments']);

// Provide postsArray (array of posts) for the client app
if (!isset($postsArray)) {
      $postsArray = [$postArray];
}

$currentUserArray = null;
if ($currentUser) {
      $currentUserArray = (is_object($currentUser) && method_exists($currentUser, 'toArray')) ? $currentUser->toArray() : (array)$currentUser;
}

$postsJson = json_encode($postsArray, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$currentUserJson = $currentUserArray !== null ? json_encode($currentUserArray, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';
?>

<div id="profileApp" v-cloak>
      <div v-for="post in posts" :key="post.id" >
            <?= $this->element('posts/post_card', ['post' => $post, 'currentUser' => $currentUser]) ?>
      </div>

      <!-- Global Confirmation Modal Element -->
      <?= $this->element('confirmation_modal') ?>

      <!-- Post Detail Modal -->
      <?= $this->element('posts/post_detail_modal') ?>
      
      <!-- Image Viewer Modal -->
      <transition name="fade">
        <div 
          v-if="imageViewer && imageViewer.isOpen"
          @click="closeImageViewer"
          class="fixed inset-0 bg-black bg-opacity-95 z-[9999] flex items-center justify-center"
        >
          <!-- Close Button -->
          <button 
            @click="closeImageViewer"
            class="absolute top-4 right-4 text-white hover:text-gray-300 transition-colors z-10"
            title="Close (Esc)"
          >
            <i data-lucide="x" class="w-8 h-8"></i>
          </button>
          
          <!-- Image Counter -->
          <div class="absolute top-4 left-1/2 transform -translate-x-1/2 text-white text-sm font-medium bg-black bg-opacity-50 px-3 py-1.5 rounded-full">
            {{ imageViewer.currentIndex + 1 }} / {{ imageViewer.images.length }}
          </div>
          
          <!-- Previous Button -->
          <button 
            v-if="imageViewer.currentIndex > 0"
            @click.stop="prevImage"
            class="absolute left-4 text-white hover:text-gray-300 transition-colors bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-3"
            title="Previous (←)"
          >
            <i data-lucide="chevron-left" class="w-6 h-6"></i>
          </button>
          
          <!-- Image Container -->
          <div 
            @click.stop
            class="max-w-7xl max-h-screen w-full h-full flex items-center justify-center p-4"
          >
            <img 
              :src="imageViewer.images[imageViewer.currentIndex]?.image_path || imageViewer.images[imageViewer.currentIndex]"
              :alt="'Image ' + (imageViewer.currentIndex + 1)"
              class="max-w-full max-h-full object-contain"
            />
          </div>
          
          <!-- Next Button -->
          <button 
            v-if="imageViewer.currentIndex < imageViewer.images.length - 1"
            @click.stop="nextImage"
            class="absolute right-4 text-white hover:text-gray-300 transition-colors bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-3"
            title="Next (→)"
          >
            <i data-lucide="chevron-right" class="w-6 h-6"></i>
          </button>
        </div>
      </transition>
</div>

<script>
      // Provide data shape expected by profile.js
      window.profileData = window.profileData || {};
      try {
            window.profileData.posts = <?= $postsJson ?: '[]' ?>;
      } catch (e) { window.profileData.posts = []; console.error('post json parse error', e); }

      try {
            window.profileData.user = <?= $currentUserJson ?: 'null' ?>;
            window.profileData.postCount = window.profileData.postCount || 1;
            window.profileData.currentUserId = <?= isset($currentUserArray['id']) ? (int)$currentUserArray['id'] : 'null' ?>;
            
            // Add user profile data for the post detail view
            if (!window.profileData.user && <?= $currentUserJson ?: 'null' ?>) {
                  const currentUser = <?= $currentUserJson ?: 'null' ?>;
                  window.profileData.user = {
                        full_name: currentUser?.full_name || 'User',
                        username: currentUser?.username || 'user',
                        avatar: currentUser?.profile_photo_path || 'https://i.pravatar.cc/150?img=1',
                        joinedDate: 'Joined recently',
                        bio: currentUser?.bio || null,
                        stats: {
                              posts: 0,
                              friends: '0',
                              likes: 0
                        }
                  };
            }
      } catch (e) { console.error('currentUser injection error', e); }
</script>

<script src="/js/profile.js?v=<?= time() ?>"></script>

<?php

