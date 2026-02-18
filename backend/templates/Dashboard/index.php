<div id="dashboardApp" class="space-y-3 sm:space-y-4" v-cloak>
  <!-- Create Post -->
  <?= $this->element('posts/post_create', ['currentUser' => $user ?? []]) ?>
  
  <!-- Feed Posts -->
  <?= $this->element('posts/post_list', [
    'posts' => $postsArray ?? [],
    'currentUser' => $user ?? [],
    'emptyMessage' => 'No posts yet. Be the first to share something!'
  ]) ?>
  
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
        <!-- Left: Photo -->
        <div class="flex-1 min-w-0 min-h-0 flex items-center justify-center bg-black relative">
          <template v-if="postDetailView.post.post_images && postDetailView.post.post_images.length > 0">
            <img
              :src="postDetailView.post.post_images[postDetailView.imageIndex].image_path"
              :alt="'Image ' + (postDetailView.imageIndex + 1)"
              class="max-w-full max-h-full object-contain"
            />
            <button
              v-if="postDetailView.imageIndex > 0"
              @click="postDetailPrevImage"
              class="absolute left-2 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-2"
            >
              <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
            <button
              v-if="postDetailView.imageIndex < postDetailView.post.post_images.length - 1"
              @click="postDetailNextImage"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-2"
            >
              <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>
            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 text-white text-xs bg-black bg-opacity-50 px-2 py-1 rounded-full">
              {{ postDetailView.imageIndex + 1 }} / {{ postDetailView.post.post_images.length }}
            </div>
          </template>
          <div v-else class="text-gray-400 text-sm">No image</div>
        </div>
        <!-- Right: Caption, user, likes, comments -->
        <div class="w-full sm:w-96 flex flex-col border-t sm:border-t-0 sm:border-l border-gray-200 bg-white overflow-hidden">
          <div class="p-3 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <button @click="closePostDetailView" class="p-1.5 hover:bg-gray-100 rounded-full" title="Close">
              <i data-lucide="x" class="w-5 h-5 text-gray-600"></i>
            </button>
            <span class="text-sm font-medium text-gray-700">Post</span>
            <div class="w-8"></div>
          </div>
          <div class="flex-1 overflow-y-auto p-3 space-y-3">
            <!-- User + caption -->
            <div class="flex gap-2">
              <img
                :src="postDetailView.post.user?.profile_photo_path || 'https://i.pravatar.cc/150?img=1'"
                :alt="postDetailView.post.user?.full_name"
                class="w-9 h-9 rounded-full object-cover flex-shrink-0"
              />
              <div class="min-w-0 flex-1">
                <a :href="`/profile/${postDetailView.post.user?.id}`" class="font-semibold text-sm text-gray-900 hover:underline">{{ postDetailView.post.user?.full_name }}</a>
                <p class="text-xs text-gray-500">{{ formatDate(postDetailView.post.created) }}</p>
                <p v-if="postDetailView.post.content_text" class="text-sm text-gray-800 whitespace-pre-wrap mt-1">{{ postDetailView.post.content_text }}</p>
              </div>
            </div>
            <!-- When viewing an image: show this image's likes & comments (separate from post) -->
            <template v-if="postDetailView.currentImageId">
              <div class="flex items-center gap-4 pt-2 border-t border-gray-100">
                <button
                  @click.prevent="toggleImageLike()"
                  :class="postDetailView.imageIsLiked ? 'text-red-500' : 'text-gray-700 hover:text-red-500'"
                  class="flex items-center gap-1.5 transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" :fill="postDetailView.imageIsLiked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l8 8Z"></path>
                  </svg>
                  <span class="text-sm font-semibold">{{ postDetailView.imageLikeCount }}</span>
                </button>
                <span class="text-sm text-gray-500">{{ postDetailView.imageComments.length }} {{ postDetailView.imageComments.length === 1 ? 'comment' : 'comments' }}</span>
              </div>
              <div class="border-t border-gray-100 pt-2">
                <p class="text-xs font-medium text-gray-500 mb-2">Comments on this photo</p>
                <div class="space-y-2 max-h-[200px] overflow-y-auto mb-2">
                  <div v-for="comment in postDetailView.imageComments" :key="comment.id" class="flex gap-2 text-xs">
                    <img :src="comment.user?.profile_photo_path || 'https://i.pravatar.cc/150?img=1'" :alt="comment.user?.full_name" class="w-6 h-6 rounded-full object-cover flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                      <a :href="`/profile/${comment.user?.id}`" class="font-semibold text-gray-900 hover:underline">{{ comment.user?.full_name }}</a>
                      <p v-if="comment.content_text" class="text-gray-800 whitespace-pre-wrap">{{ comment.content_text }}</p>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-1.5">
                  <img :src="user.avatar" alt="" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                  <input
                    v-model="postDetailView.imageNewComment"
                    @keyup.enter="submitImageComment()"
                    type="text"
                    placeholder="Write a comment..."
                    class="flex-1 px-3 py-1.5 bg-gray-50 rounded-full text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <button @click="submitImageComment()" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-full">
                    <i data-lucide="send" class="w-4 h-4"></i>
                  </button>
                </div>
              </div>
            </template>
            <!-- When no image or post-level: show post likes & comments -->
            <template v-else>
              <div class="flex items-center gap-4 pt-2 border-t border-gray-100">
                <button
                  @click.prevent="toggleLike(postDetailView.post.id)"
                  :class="postDetailView.post.is_liked ? 'text-red-500' : 'text-gray-700 hover:text-red-500'"
                  class="flex items-center gap-1.5 transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" :fill="postDetailView.post.is_liked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l8 8Z"></path>
                  </svg>
                  <span class="text-sm font-semibold">{{ postDetailView.post.like_count || 0 }}</span>
                </button>
                <button
                  @click="handleOpenComment(postDetailView.post.id)"
                  class="flex items-center gap-1.5 text-gray-700 hover:text-blue-500"
                >
                  <i data-lucide="message-circle" class="w-5 h-5"></i>
                  <span class="text-sm font-semibold">{{ postDetailView.post.comment_count || 0 }}</span>
                </button>
              </div>
              <div class="border-t border-gray-100 pt-2" v-for="post in (postDetailView.post ? [postDetailView.post] : [])" :key="'modal-' + post.id">
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

<!-- Emoji Picker (Outside Vue App) -->
<div 
  id="emojiPickerContainer"
  class="fixed z-50 mt-2"
  style="left: 50%; transform: translateX(-50%); visibility: hidden; opacity: 0; pointer-events: none;"
>
  <div class="shadow-2xl rounded-lg overflow-hidden border border-gray-200 bg-white">
    <emoji-picker class="light"></emoji-picker>
  </div>
</div>

<script>
// Pass data to dashboard.js
window.dashboardData = {
  user: {
    id: <?= json_encode($user['id'] ?? null) ?>,
    username: <?= json_encode($user['username'] ?? 'user') ?>,
    avatar: <?= json_encode($user['profile_photo_path'] ?? 'https://i.pravatar.cc/150?img=1') ?>
  },
    posts: <?= json_encode($postsArray ?? []) ?>
};
</script>

<script src="/js/dashboard.js?v=<?= time() ?>"></script>
