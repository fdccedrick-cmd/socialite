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

      <!-- Post Detail View (Facebook-style: photo left, caption/comments/likes right) -->
      <transition name="fade">
        <div
          v-if="postDetailView?.isOpen && postDetailView?.post"
          class="fixed inset-0 z-[9998] flex items-center justify-center bg-black bg-opacity-90"
          @click.self="closePostDetailView"
        >
          <div class="w-full h-full max-w-6xl max-h-[90vh] m-4 flex flex-col sm:flex-row bg-white rounded-xl overflow-hidden shadow-2xl" @click.stop>
            <div class="flex-1 min-w-0 min-h-0 flex items-center justify-center bg-black relative">
              <template v-if="postDetailView.post.post_images && postDetailView.post.post_images.length > 0">
                <img
                  :key="'img-' + postDetailView.imageIndex"
                  :src="postDetailView.post.post_images[postDetailView.imageIndex].image_path"
                  :alt="'Image ' + (postDetailView.imageIndex + 1)"
                  class="max-w-full max-h-full object-contain"
                />
                <button 
                  v-show="postDetailView.post.post_images.length > 1" 
                  :key="'prev-' + postDetailView.imageIndex"
                  @click.stop="postDetailPrevImage" 
                  class="absolute left-2 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-2 transition-colors z-10"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                    <polyline points="15 18 9 12 15 6"></polyline>
                  </svg>
                </button>
                <button 
                  v-show="postDetailView.post.post_images.length > 1" 
                  :key="'next-' + postDetailView.imageIndex"
                  @click.stop="postDetailNextImage" 
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-2 transition-colors z-10"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                    <polyline points="9 18 15 12 9 6"></polyline>
                  </svg>
                </button>
                <div class="absolute bottom-2 left-1/2 -translate-x-1/2 text-white text-xs bg-black bg-opacity-50 px-2 py-1 rounded-full">
                  {{ postDetailView.imageIndex + 1 }} / {{ postDetailView.post.post_images.length }}
                </div>
              </template>
              <div v-else class="text-gray-400 text-sm">No image</div>
            </div>
            <div class="w-full sm:w-96 flex flex-col border-t sm:border-t-0 sm:border-l border-gray-200 bg-white overflow-hidden">
              <div class="px-3 py-2.5 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <button @click="closePostDetailView" class="p-1 hover:bg-gray-100 rounded-full transition-colors" title="Close">
                  <i data-lucide="x" class="w-5 h-5 text-gray-700"></i>
                </button>
                <span class="text-sm font-semibold text-gray-900">Post Details</span>
                <div class="w-6"></div>
              </div>
              <div class="flex-1 overflow-y-auto">
                <!-- Post Author & Caption -->
                <div class="p-3 border-b border-gray-100">
                  <div class="flex gap-2.5">
                    <img :src="postDetailView.post.user?.profile_photo_path || 'https://i.pravatar.cc/150?img=1'" :alt="postDetailView.post.user?.full_name" class="w-10 h-10 rounded-full object-cover flex-shrink-0" />
                    <div class="min-w-0 flex-1">
                      <a :href="`/profile/${postDetailView.post.user?.id}`" class="font-semibold text-sm text-gray-900 hover:underline">{{ postDetailView.post.user?.full_name }}</a>
                      <p class="text-xs text-gray-500 mt-0.5">{{ formatDate(postDetailView.post.created) }}</p>
                      <p v-if="postDetailView.post.content_text" class="text-sm text-gray-800 whitespace-pre-wrap mt-2 leading-relaxed">{{ postDetailView.post.content_text }}</p>
                    </div>
                  </div>
                </div>
                
                <!-- Individual Image Likes & Comments -->
                <template v-if="postDetailView.currentImageId">
                  <div class="px-3 py-2.5 bg-gray-50 border-b border-gray-100">
                    <div class="flex items-center gap-4">
                      <button @click.prevent="toggleImageLike()" :class="postDetailView.imageIsLiked ? 'text-red-500' : 'text-gray-700 hover:text-red-500'" class="flex items-center gap-1.5 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" :fill="postDetailView.imageIsLiked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                          <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l8 8Z"></path>
                        </svg>
                        <span class="text-sm font-semibold">{{ postDetailView.imageLikeCount }}</span>
                      </button>
                      <span class="text-sm text-gray-600">{{ postDetailView.imageComments.length }} {{ postDetailView.imageComments.length === 1 ? 'comment' : 'comments' }}</span>
                    </div>
                  </div>
                  
                  <!-- Image Comments Section -->
                  <div class="flex-1 overflow-y-auto p-3">
                    <p class="text-xs font-semibold text-gray-600 mb-2.5 uppercase tracking-wide">Photo Comments</p>
                    <div v-if="postDetailView.imageComments.length === 0" class="text-center py-8">
                      <i data-lucide="message-circle" class="w-12 h-12 mx-auto text-gray-300 mb-2"></i>
                      <p class="text-sm text-gray-500">No comments yet</p>
                      <p class="text-xs text-gray-400 mt-1">Be the first to comment on this photo</p>
                    </div>
                    <div v-else class="space-y-3 mb-3">
                      <div v-for="comment in postDetailView.imageComments" :key="comment.id" class="flex gap-2.5">
                        <img :src="comment.user?.profile_photo_path || 'https://i.pravatar.cc/150?img=1'" :alt="comment.user?.full_name" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                        <div class="flex-1 min-w-0 bg-gray-50 rounded-2xl px-3 py-2">
                          <a :href="`/profile/${comment.user?.id}`" class="font-semibold text-xs text-gray-900 hover:underline">{{ comment.user?.full_name }}</a>
                          <p v-if="comment.content_text" class="text-sm text-gray-800 whitespace-pre-wrap mt-0.5">{{ comment.content_text }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Comment Input -->
                  <div class="p-3 border-t border-gray-100 bg-white">
                    <div class="flex items-center gap-2">
                      <img :src="user.avatar" alt="" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                      <input v-model="postDetailView.imageNewComment" @keyup.enter="submitImageComment()" type="text" placeholder="Add a comment..." class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                      <button @click="submitImageComment()" :disabled="!postDetailView.imageNewComment?.trim()" :class="postDetailView.imageNewComment?.trim() ? 'text-blue-600 hover:bg-blue-50' : 'text-gray-300 cursor-not-allowed'" class="p-2 rounded-full transition-colors">
                        <i data-lucide="send" class="w-5 h-5"></i>
                      </button>
                    </div>
                  </div>
                </template>
                
                <!-- General Post Likes & Comments -->
                <template v-else>
                  <div class="px-3 py-2.5 bg-gray-50 border-b border-gray-100">
                    <div class="flex items-center gap-4">
                      <button @click.prevent="toggleLike(postDetailView.post.id)" :class="postDetailView.post.is_liked ? 'text-red-500' : 'text-gray-700 hover:text-red-500'" class="flex items-center gap-1.5 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" :fill="postDetailView.post.is_liked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                          <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l8 8Z"></path>
                        </svg>
                        <span class="text-sm font-semibold">{{ postDetailView.post.like_count || 0 }}</span>
                      </button>
                      <button @click="handleOpenComment(postDetailView.post.id)" class="flex items-center gap-1.5 text-gray-700 hover:text-blue-500 transition-colors">
                        <i data-lucide="message-circle" class="w-5 h-5"></i>
                        <span class="text-sm font-semibold">{{ postDetailView.post.comment_count || 0 }}</span>
                      </button>
                    </div>
                  </div>
                  
                  <!-- Post Comments Section -->
                  <div class="flex-1 overflow-y-auto p-3" v-for="post in (postDetailView.post ? [postDetailView.post] : [])" :key="'modal-' + post.id">
                    <?= $this->element('comments/comment_list', ['post' => isset($postsArray[0]) ? $postsArray[0] : []]) ?>
                    <?= $this->element('comments/comment_input', ['post' => isset($postsArray[0]) ? $postsArray[0] : []]) ?>
                  </div>
                </template>
              </div>
            </div>
          </div>
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

