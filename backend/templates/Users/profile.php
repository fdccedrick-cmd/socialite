<div id="profileApp" class="space-y-3 sm:space-y-4 lg:space-y-6" v-cloak>
  <!-- Profile Header Card -->
  <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 lg:p-8 mb-3 sm:mb-4 lg:mb-6">
    <div class="flex flex-col md:flex-row items-start md:items-center gap-3 sm:gap-4 lg:gap-6">
      <!-- Profile Photo -->
      <div class="shrink-0 mx-auto md:mx-0">
        <img 
          :src="user.avatar" 
          :alt="user.full_name" 
          class="w-20 h-20 sm:w-24 sm:h-24 lg:w-32 lg:h-32 rounded-full object-cover border-2 sm:border-4 border-gray-100"
        />
      </div>
      
      <!-- Profile Info -->
      <div class="flex-1 text-center md:text-left w-full">
        <div class="mb-2 sm:mb-3">
          <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900">{{ user.full_name }}</h1>
          <p class="text-gray-500 text-sm sm:text-base mt-0.5 sm:mt-1">@{{ user.username }}</p>
          <p class="text-gray-400 text-xs sm:text-sm mt-0.5 sm:mt-1">{{ user.joinedDate }}</p>
        </div>
        
        <div class="mb-3 sm:mb-4">
          <p v-if="user.bio" class="text-gray-700 text-xs sm:text-sm lg:text-base">{{ user.bio }}</p>
          <p v-else class="text-gray-400 text-xs sm:text-sm lg:text-base italic">No bio yet</p>
        </div>
        
        <!-- Stats -->
        <div class="flex justify-center md:justify-start gap-4 sm:gap-6 lg:gap-8 mb-3 sm:mb-4">
          <div>
            <span class="font-bold text-gray-900 text-sm sm:text-base lg:text-lg">{{ user.stats.posts }}</span>
            <span class="text-gray-500 text-xs sm:text-sm ml-1">Posts</span>
          </div>
          <div>
            <span class="font-bold text-gray-900 text-sm sm:text-base lg:text-lg">{{ user.stats.friends }}</span>
            <span class="text-gray-500 text-xs sm:text-sm ml-1">Friends</span>
          </div>
          <div>
            <span class="font-bold text-gray-900 text-sm sm:text-base lg:text-lg">{{ user.stats.likes }}</span>
            <span class="text-gray-500 text-xs sm:text-sm ml-1">Likes</span>
          </div>
        </div>
      </div>
      
      <!-- Edit Profile Button -->
      <div class="shrink-0 md:self-start w-full md:w-auto">
        <button @click="openEditModal" class="flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-1.5 sm:py-2 pr-1.5 sm:pr-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors w-full md:w-auto">
          <i data-lucide="settings" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-700"></i>
          <span class="text-xs sm:text-sm font-medium text-gray-700"></span>
        </button>
      </div>
    </div>
  </div>
  
  <!-- Tabs -->
  <div class="bg-white rounded-t-xl sm:rounded-t-2xl shadow-sm border border-gray-100 border-b-0">
    <div class="flex border-b border-gray-200">
      <button 
        @click="activeTab = 'posts'" 
        :class="{'border-b-2 border-gray-900 text-gray-900': activeTab === 'posts', 'text-gray-500': activeTab !== 'posts'}"
        class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4 font-medium text-xs sm:text-sm transition-colors"
      >
        <i data-lucide="layout-grid" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
        <span>Posts</span>
      </button>
      <button 
        @click="activeTab = 'saved'" 
        :class="{'border-b-2 border-gray-900 text-gray-900': activeTab === 'saved', 'text-gray-500': activeTab !== 'saved'}"
        class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4 font-medium text-xs sm:text-sm transition-colors"
      >
        <i data-lucide="bookmark" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
        <span>Saved</span>
      </button>
    </div>
  </div>
  
  <!-- Tab Content -->
  <div class="bg-white rounded-b-xl sm:rounded-b-2xl shadow-sm border border-gray-100">
    <!-- Posts Tab -->
    <div v-if="activeTab === 'posts'" class="p-3 sm:p-4 lg:p-6">
      <?= $this->element('posts/post_list', [
        'posts' => $postsArray ?? [],
        'currentUser' => $user ?? [],
        'emptyMessage' => 'No posts yet. Share your first post to get started!'
      ]) ?>
    </div>
    
    <!-- Saved Tab -->
    <div v-if="activeTab === 'saved'" class="p-3 sm:p-4 lg:p-6">
      <div class="text-center py-12 sm:py-16">
        <i data-lucide="bookmark" class="w-12 h-12 sm:w-16 sm:h-16 text-gray-300 mx-auto mb-3 sm:mb-4"></i>
        <p class="text-gray-500 text-base sm:text-lg">No saved posts</p>
        <p class="text-gray-400 text-xs sm:text-sm mt-1 sm:mt-2">Save posts to view them later</p>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <?= $this->element('users/edit_profile_modal') ?>
  
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
  // Pass data to profile.js
  window.profileData = {
    currentUserId: <?= json_encode($currentUserId ?? null) ?>,
    posts: <?= json_encode($postsArray ?? []) ?>,
    user: {
      full_name: <?= json_encode(!empty($user['full_name']) ? $user['full_name'] : (!empty($user['username']) ? $user['username'] : 'User')) ?>,
      username: <?= json_encode(!empty($user['username']) ? $user['username'] : 'user') ?>,
      avatar: <?= json_encode(!empty($user['profile_photo_path']) ? $user['profile_photo_path'] : '/img/default/default_avatar.jpg') ?>,
      joinedDate: <?php 
        $joinedDate = 'Joined recently';
        if (!empty($user['created'])) {
          try {
            $dateStr = is_string($user['created']) ? $user['created'] : (is_object($user['created']) ? $user['created']->format('Y-m-d') : '');
            if ($dateStr) {
              $timestamp = strtotime($dateStr);
              if ($timestamp !== false) {
                $joinedDate = 'Joined ' . date('M Y', $timestamp);
              }
            }
          } catch (Exception $e) {
            $joinedDate = 'Joined recently';
          }
        }
        echo json_encode($joinedDate);
      ?>,
      bio: <?= json_encode($user['bio'] ?? null) ?>
    },
    postCount: <?= json_encode($postCount ?? 0) ?>,
    likes: <?= json_encode($userLikeCount ?? 0) ?>
  };
</script>

<?php $this->start('script'); ?>
<!-- Shared Utilities -->
<script src="/js/shared-post-utils.js?v=<?= time() ?>"></script>
<script src="/js/shared-post-editing-utils.js?v=<?= time() ?>"></script>
<script src="/js/profile.js?v=<?= time() ?>"></script>
<?php $this->end(); ?>
