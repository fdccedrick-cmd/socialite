<script>
// Pass data to profile.js (moved outside the Vue-mounted template to avoid Vue compile errors)
<?php 
?>
window.profileData = {
    currentUserId: <?= json_encode($currentUserId ?? null) ?>,
    profileUserId: <?= json_encode($user['id'] ?? null) ?>,
    isOwnProfile: <?= json_encode($isOwnProfile ?? true) ?>,
    friendshipStatus: <?= json_encode($friendshipStatus ?? null) ?>,
    friendshipId: <?= json_encode($friendshipId ?? null) ?>,
    isSender: <?= json_encode($isSender ?? false) ?>,
    mutualFriendsCount: <?= json_encode($mutualFriendsCount ?? 0) ?>,
    friendsCount: <?= json_encode($friendsCount ?? 0) ?>,
    posts: <?= json_encode($postsArray ?? []) ?>,
    user: {
        full_name: <?= json_encode(!empty($user['full_name']) ? $user['full_name'] : (!empty($user['username']) ? $user['username'] : 'User')) ?>,
        username: <?= json_encode(!empty($user['username']) ? $user['username'] : 'user') ?>,
        avatar: <?= json_encode(!empty($user['profile_photo_path']) ? $user['profile_photo_path'] : 'https://i.pravatar.cc/150?img=1') ?>,
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
        bio: <?= json_encode($user['bio'] ?? null) ?>,
        address: <?= json_encode($user['address'] ?? null) ?>,
        relationship_status: <?= json_encode($user['relationship_status'] ?? null) ?>,
        contact_links: <?= json_encode($user['contact_links'] ?? null) ?>
    },
    postCount: <?= json_encode($postCount ?? 0) ?>,
    likes: <?= json_encode($userLikeCount ?? 0) ?>
};

// Debug: Log the profileData immediately
console.log('🔍 Profile Data Debug:', {
    likes: window.profileData.likes,
    'typeof likes': typeof window.profileData.likes,
    postCount: window.profileData.postCount,
    postsLength: window.profileData.posts?.length,
    firstPostLikeCount: window.profileData.posts?.[0]?.like_count,
    bio: window.profileData.user.bio
});
</script>

<div id="profileApp" class="space-y-3 sm:space-y-4 lg:space-y-6" v-cloak>
  <!-- Profile Header Card -->
  <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 lg:p-8 mb-3 sm:mb-4 lg:mb-6">
    <div class="flex flex-col md:flex-row items-start md:items-center gap-3 sm:gap-4 lg:gap-6">
      <!-- Profile Photo -->
      <div class="shrink-0 mx-auto md:mx-0">
        <img 
          :src="user.avatar" 
          :alt="user.full_name" 
          class="w-20 h-20 sm:w-24 sm:h-24 lg:w-32 lg:h-32 rounded-full object-cover border-2 sm:border-4 border-gray-100 dark:border-gray-600"
        />
      </div>
      
      <!-- Profile Info -->
      <div class="flex-1 text-center md:text-left w-full">
        <div class="mb-2 sm:mb-3">
          <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">{{ user.full_name }}</h1>
          <p class="text-gray-500 dark:text-gray-400 text-sm sm:text-base mt-0.5 sm:mt-1">@{{ user.username }}</p>
          <p class="text-gray-400 dark:text-gray-500 text-xs sm:text-sm mt-0.5 sm:mt-1">{{ user.joinedDate }}</p>
        </div>
        
        <div class="mb-3 sm:mb-4">
          <p v-if="user.bio" class="text-gray-700 dark:text-gray-300 text-xs sm:text-sm lg:text-base">{{ user.bio }}</p>
          <p v-else class="text-gray-400 dark:text-gray-500 text-xs sm:text-sm lg:text-base italic">No bio yet</p>
        </div>
        
        <!-- Stats -->
        <div class="flex justify-center md:justify-start gap-4 sm:gap-6 lg:gap-8 mb-3 sm:mb-4">
          <div>
            <span class="font-bold text-gray-900 dark:text-white text-sm sm:text-base lg:text-lg">{{ user.stats.posts }}</span>
            <span class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm ml-1">Posts</span>
          </div>
          <div>
            <span class="font-bold text-gray-900 dark:text-white text-sm sm:text-base lg:text-lg">{{ user.stats.friends }}</span>
            <span class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm ml-1">Friends</span>
          </div>
          <div>
            <span class="font-bold text-gray-900 dark:text-white text-sm sm:text-base lg:text-lg">{{ user.stats.likes }}</span>
            <span class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm ml-1">Likes</span>
          </div>
        </div>
        
      </div>
      
      <!-- Action Buttons -->
      <div class="shrink-0 md:self-start w-full md:w-auto">
        <!-- Edit Profile Button (Own Profile) -->
        <button 
          v-if="isOwnProfile"
          @click="openEditModal" 
          class="flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-1.5 sm:py-2 pr-1.5 sm:pr-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors w-full md:w-auto"
        >
          <i data-lucide="settings" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-700 dark:text-gray-300"></i>
          <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">Edit Profile</span>
        </button>

        <!-- Friend Action Buttons (Other's Profile) -->
        <div v-if="!isOwnProfile" class="flex gap-2">
          <!-- Add Friend Button -->
          <button 
            v-if="friendshipStatus === null"
            @click="sendFriendRequest"
            class="flex items-center justify-center gap-1.5 sm:gap-2 px-4 sm:px-5 py-2 sm:py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors w-full md:w-auto font-medium text-xs sm:text-sm"
          >
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Add Friend</span>
          </button>

          <!-- Request Sent (temporarily shown for 2 seconds) -->
          <button 
            v-if="friendshipStatus === 'pending' && isSender && showRequestSent"
            disabled
            class="flex items-center justify-center gap-1.5 sm:gap-2 px-4 sm:px-5 py-2 sm:py-2.5 bg-gray-200 text-gray-700 rounded-lg cursor-default w-full md:w-auto font-medium text-xs sm:text-sm"
          >
            <i data-lucide="clock" class="w-4 h-4"></i>
            <span>Request Sent</span>
          </button>

          <!-- Cancel Request Button (shown after 2 seconds) -->
          <button 
            v-if="friendshipStatus === 'pending' && isSender && !showRequestSent"
            @click="cancelFriendRequest"
            class="flex items-center justify-center gap-1.5 sm:gap-2 px-4 sm:px-5 py-2 sm:py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors w-full md:w-auto font-medium text-xs sm:text-sm"
          >
            <i data-lucide="x-circle" class="w-4 h-4"></i>
            <span>Cancel Request</span>
          </button>

          <!-- Accept/Reject Buttons (Pending - Other User Sent) -->
          <template v-if="friendshipStatus === 'pending' && !isSender">
            <button 
              @click="acceptFriendRequest"
              class="flex items-center justify-center gap-1.5 sm:gap-2 px-4 sm:px-5 py-2 sm:py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium text-xs sm:text-sm"
            >
              <i data-lucide="check" class="w-4 h-4"></i>
              <span>Accept</span>
            </button>
            <button 
              @click="rejectFriendRequest"
              class="flex items-center justify-center gap-1.5 sm:gap-2 px-4 sm:px-5 py-2 sm:py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors font-medium text-xs sm:text-sm"
            >
              <i data-lucide="x" class="w-4 h-4"></i>
              <span>Reject</span>
            </button>
          </template>

          <!-- Friends Button with Dropdown -->
          <div v-if="friendshipStatus === 'accepted'" class="relative">
            <button 
              @click.stop="showFriendsMenu = !showFriendsMenu"
              class="flex items-center justify-center gap-1.5 sm:gap-2 px-4 sm:px-5 py-2 sm:py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors w-full md:w-auto font-medium text-xs sm:text-sm"
            >
              <i data-lucide="user-check" class="w-4 h-4"></i>
              <span>Friends</span>
              <i data-lucide="chevron-down" class="w-3 h-3"></i>
            </button>

            <!-- Dropdown Menu -->
            <div 
              v-if="showFriendsMenu"
              v-click-outside="closeFriendsMenu"
              class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-10"
            >
              <button 
                @click.stop="unfriend"
                class="w-full flex items-center gap-2 px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
              >
                <i data-lucide="user-minus" class="w-4 h-4"></i>
                <span>Unfriend</span>
              </button>
            </div>
          </div>

          <!-- Message Button (optional) -->
          <button 
            v-if="friendshipStatus === 'accepted'"
            class="flex items-center justify-center gap-1.5 sm:gap-2 px-4 sm:px-5 py-2 sm:py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-700 dark:text-gray-300 rounded-lg transition-colors font-medium text-xs sm:text-sm"
          >
            <i data-lucide="message-circle" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Message</span>
          </button>
        </div>
    </div>
  </div>
  
  <!-- Personal Details Section (Above Tabs - Facebook Style) -->
  <div v-if="user.address || user.relationship_status || (user.contactLinksArray && user.contactLinksArray.length > 0)" class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-5 lg:p-6">
    <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-3 sm:mb-4">Personal Details</h3>
    <div class="space-y-3">
      <div v-if="user.address" class="flex items-start gap-3">
        <div class="mt-0.5">
          <i data-lucide="map-pin" class="w-5 h-5 text-gray-500 dark:text-gray-400"></i>
        </div>
        <div>
          <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-0.5">Address</p>
          <p class="text-sm sm:text-base text-gray-900 dark:text-white">{{ user.address }}</p>
        </div>
      </div>
      
      <div v-if="user.relationship_status" class="flex items-start gap-3">
        <div class="mt-0.5">
          <i data-lucide="heart" class="w-5 h-5 text-gray-500 dark:text-gray-400"></i>
        </div>
        <div>
          <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-0.5">Relationship Status</p>
          <p class="text-sm sm:text-base text-gray-900 dark:text-white capitalize">{{ user.relationship_status }}</p>
        </div>
      </div>
      
      <div v-if="user.contactLinksArray && user.contactLinksArray.length > 0" class="flex items-start gap-3">
        <div class="mt-0.5">
          <i data-lucide="link" class="w-5 h-5 text-gray-500 dark:text-gray-400"></i>
        </div>
        <div class="flex-1">
          <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-2">Contact Links</p>
          <div class="flex flex-wrap gap-2">
            <a 
              v-for="(link, index) in user.contactLinksArray" 
              :key="index"
              :href="link.url"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors text-xs sm:text-sm font-medium"
            >
              <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
              <span>{{ link.label }}</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Tabs -->
  <div class="bg-white dark:bg-gray-800 rounded-t-xl sm:rounded-t-2xl shadow-sm border border-gray-100 dark:border-gray-700 border-b-0">
    <div class="flex border-b border-gray-200 dark:border-gray-700">
      <button 
        @click="activeTab = 'posts'" 
        :class="{'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white': activeTab === 'posts', 'text-gray-500 dark:text-gray-400': activeTab !== 'posts'}"
        class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4 font-medium text-xs sm:text-sm transition-colors"
      >
        <i data-lucide="layout-grid" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
        <span>Posts</span>
      </button>
      <button 
        @click="activeTab = 'saved'" 
        :class="{'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white': activeTab === 'saved', 'text-gray-500 dark:text-gray-400': activeTab !== 'saved'}"
        class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4 font-medium text-xs sm:text-sm transition-colors"
      >
        <i data-lucide="bookmark" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
        <span>Saved</span>
      </button>
    </div>
  </div>
  
  <!-- Tab Content -->
  <div class="bg-white dark:bg-gray-800 rounded-b-xl sm:rounded-b-2xl w-full ">
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
        <i data-lucide="bookmark" class="w-12 h-12 sm:w-16 sm:h-16 text-gray-300 dark:text-gray-600 mx-auto mb-3 sm:mb-4"></i>
        <p class="text-gray-500 dark:text-gray-400 text-base sm:text-lg">No saved posts</p>
        <p class="text-gray-400 dark:text-gray-500 text-xs sm:text-sm mt-1 sm:mt-2">Save posts to view them later</p>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <?= $this->element('users/edit_profile_modal') ?>

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

<?php $this->start('script'); ?>
<!-- WebSocket Manager for real-time updates -->
<script src="/js/websocket-manager.js?v=<?= time() ?>"></script>
<script src="/js/profile.js?v=<?= time() ?>"></script>
<?php $this->end(); ?>
