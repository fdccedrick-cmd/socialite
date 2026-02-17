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
      v-if="imageViewer.isOpen"
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

<script src="/js/dashboard.js"></script>
