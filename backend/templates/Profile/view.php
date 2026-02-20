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
        profile_photo_path: <?= json_encode(!empty($user['profile_photo_path']) ? $user['profile_photo_path'] : null) ?>,
        avatar: <?= json_encode(!empty($user['profile_photo_path']) ? $user['profile_photo_path'] : '/img/default/default_avatar.jpg') ?>,
        coverPhoto: <?= json_encode(!empty($user['cover_photo_path']) ? $user['cover_photo_path'] : null) ?>,
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

<div id="profileApp" class="space-y-2 sm:space-y-3 md:space-y-3" v-cloak>
  <!-- Cover Photo Section (Facebook Style) -->
  <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-visible">
    <div class="relative">
      <!-- Cover Photo -->
      <div 
        @click="user.coverPhoto ? openCoverPhotoView() : null"
        class="relative w-full h-32 sm:h-40 md:h-48 lg:h-56 bg-gradient-to-br from-blue-400 via-purple-400 to-pink-400 dark:from-blue-600 dark:via-purple-600 dark:to-pink-600 group rounded-t-xl sm:rounded-t-2xl"
        :class="{ 'cursor-pointer': user.coverPhoto }"
        :style="user.coverPhoto ? `background-image: url('${user.coverPhoto}'); background-size: cover; background-position: center;` : ''"
      >
        <!-- Hover overlay for cover photo -->
        <div v-if="user.coverPhoto" class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100 pointer-events-none">
          <i data-lucide="zoom-in" class="w-8 h-8 sm:w-10 sm:h-10 text-white drop-shadow-lg"></i>
        </div>
        
        <!-- Edit Cover Photo Button (Own Profile) - Positioned at TOP RIGHT of cover -->
        <button 
          v-if="isOwnProfile"
          @click.stop="openCoverPhotoUpload"
          class="absolute top-2 right-2 sm:top-3 sm:right-3 md:top-4 md:right-4 flex items-center gap-1.5 sm:gap-2 px-2.5 py-1.5 sm:px-3 sm:py-2 md:px-4 md:py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg shadow-lg transition-colors z-10"
        >
          <i data-lucide="camera" class="w-3 h-3 sm:w-3.5 sm:h-3.5 md:w-4 md:h-4 text-gray-700 dark:text-gray-300"></i>
          <span class="text-[10px] sm:text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300">{{ user.coverPhoto ? 'Edit Cover' : 'Add Cover' }}</span>
        </button>
      </div>
      
      <!-- Profile Info Overlay -->
      <div class="relative -mt-12 sm:-mt-14 px-4 sm:px-6 md:px-8 lg:px-12 pb-4 sm:pb-6">
        <!-- Settings Icon (Own Profile) - Top Right -->
        <button 
          v-if="isOwnProfile"
          @click="openEditModal" 
          class="absolute top-2 right-2 sm:top-3 sm:right-4 md:right-6 lg:right-8 p-2 sm:p-2.5 rounded-full bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 shadow-md hover:shadow-lg transition-all z-10"
          title="Edit Profile"
        >
          <i data-lucide="settings" class="w-4 h-4 sm:w-5 sm:h-5 text-gray-700 dark:text-gray-300"></i>
        </button>
        
        <div class="flex flex-col sm:flex-row items-center sm:items-end gap-3 sm:gap-4 md:gap-6">
          <!-- Profile Photo -->
          <div class="shrink-0 relative group">
            <img 
              @click="handleProfilePhotoClick"
              :src="user.avatar" 
              :alt="user.full_name" 
              class="w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 lg:w-36 lg:h-36 rounded-full object-cover border-4 border-white dark:border-gray-800 shadow-lg bg-white dark:bg-gray-800 cursor-pointer transition-all duration-200 hover:shadow-xl"
            />
            <!-- Hover overlay for profile photo -->
            <div class="absolute inset-0 rounded-full bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100 pointer-events-none">
              <i :data-lucide="isUsingDefaultAvatar() ? 'edit' : 'zoom-in'" class="w-6 h-6 sm:w-8 sm:h-8 text-white drop-shadow-lg"></i>
            </div>
          </div>
          
          <!-- User Info -->
          <div class="flex-1 text-center sm:text-left pb-2 min-w-0 max-w-full">
            <h1 class="text-base sm:text-xl md:text-xl lg:text-xl font-bold text-gray-900 dark:text-white truncate px-2 sm:px-0">{{ user.full_name }}</h1>
            <p class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm md:text-base mt-0.5 sm:mt-1 truncate px-2 sm:px-0">@{{ user.username }}</p>
          </div>
          
          <!-- Action Buttons - Friend action only (not edit profile) -->
          <div v-if="!isOwnProfile" class="shrink-0 sm:self-end pb-2 w-full sm:w-auto flex justify-center sm:justify-end">

            <!-- Add Friend Button -->
            <button 
              v-if="friendshipStatus === null"
              @click="sendFriendRequest"
              class="flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium text-xs sm:text-sm"
            >
              <i data-lucide="user-plus" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
              <span>Add Friend</span>
            </button>

            <!-- Request Sent (temporarily shown for 2 seconds) -->
            <button 
              v-if="friendshipStatus === 'pending' && isSender && showRequestSent"
              disabled
              class="flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg cursor-default font-medium text-xs sm:text-sm"
            >
              <i data-lucide="clock" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
              <span class="hidden xs:inline">Request Sent</span>
              <span class="xs:hidden">Sent</span>
            </button>

            <!-- Cancel Request Button (shown after 2 seconds) -->
            <button 
              v-if="friendshipStatus === 'pending' && isSender && !showRequestSent"
              @click="cancelFriendRequest"
              class="flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors font-medium text-xs sm:text-sm"
            >
              <i data-lucide="x-circle" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
              <span class="hidden xs:inline">Cancel Request</span>
              <span class="xs:hidden">Cancel</span>
            </button>

            <!-- Accept/Reject Buttons (Pending - Other User Sent) -->
            <template v-if="friendshipStatus === 'pending' && !isSender">
              <button 
                @click="acceptFriendRequest"
                class="flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium text-xs sm:text-sm"
              >
                <i data-lucide="check" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                <span>Accept</span>
              </button>
              <button 
                @click="rejectFriendRequest"
                class="flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors font-medium text-xs sm:text-sm"
              >
                <i data-lucide="x" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                <span>Reject</span>
              </button>
            </template>

            <!-- Friends Button with Dropdown -->
            <div v-if="friendshipStatus === 'accepted'" class="relative z-[9999]">
              <button 
                @click.stop="showFriendsMenu = !showFriendsMenu"
                class="flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors font-medium text-xs sm:text-sm"
              >
                <i data-lucide="user-check" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                <span>Friends</span>
                <i data-lucide="chevron-down" class="w-2.5 h-2.5 sm:w-3 sm:h-3"></i>
              </button>

              <!-- Dropdown Menu -->
              <div 
                v-if="showFriendsMenu"
                v-click-outside="closeFriendsMenu"
                class="absolute right-0 mt-2 w-40 sm:w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-[9999]"
              >
                <button 
                  @click.stop="unfriend"
                  class="w-full flex items-center gap-2 px-3 sm:px-4 py-2 text-left text-xs sm:text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                >
                  <i data-lucide="user-minus" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                  <span>Unfriend</span>
                </button>
              </div>
            </div>

            <!-- Message Button (optional) -->
            <!-- <button 
              v-if="friendshipStatus === 'accepted'"
              class="flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg transition-colors font-medium text-xs sm:text-sm"
            >
              <i data-lucide="message-circle" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
              <span class="hidden sm:inline">Message</span>
            </button> -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bio and Additional Info Card -->
  <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-3 sm:p-4">
    <div class="space-y-2 sm:space-y-2.5">
      <!-- Bio -->
      <div>
        <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1 uppercase">Bio</p>
        <p v-if="user.bio" class="text-xs sm:text-sm text-gray-900 dark:text-white leading-relaxed">{{ user.bio }}</p>
        <p v-else class="text-xs sm:text-sm text-gray-400 dark:text-gray-500 italic">No bio yet</p>
      </div>
      
      <!-- Joined Date -->
      <div>
        <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1 uppercase">Joined</p>
        <p class="text-xs sm:text-sm text-gray-900 dark:text-white">{{ user.joinedDate }}</p>
      </div>
      
      <!-- Stats -->
      <div class="border-t border-gray-200 dark:border-gray-700 pt-2 sm:pt-2.5">
        <div class="grid grid-cols-3 gap-2 sm:gap-3 text-center">
          <div>
            <p class="text-base sm:text-lg md:text-xl font-bold text-gray-900 dark:text-white">{{ user.stats.posts }}</p>
            <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 dark:text-gray-400">Posts</p>
          </div>
          <div>
            <p class="text-base sm:text-lg md:text-xl font-bold text-gray-900 dark:text-white">{{ user.stats.friends }}</p>
            <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 dark:text-gray-400">Friends</p>
          </div>
          <div>
            <p class="text-base sm:text-lg md:text-xl font-bold text-gray-900 dark:text-white">{{ user.stats.likes }}</p>
            <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 dark:text-gray-400">Likes</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Personal Details Section (Above Tabs - Facebook Style) -->
  <div v-if="user.address || user.relationship_status || (user.contactLinksArray && user.contactLinksArray.length > 0)" class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-3 sm:p-4">
    <h3 class="text-xs sm:text-sm md:text-base font-semibold text-gray-900 dark:text-white mb-2 sm:mb-2.5">Personal Details</h3>
    <div class="space-y-2 sm:space-y-2.5">
      <div v-if="user.address" class="flex items-start gap-2 sm:gap-2.5">
        <div class="mt-0.5">
          <i data-lucide="map-pin" class="w-3.5 h-3.5 sm:w-4 sm:h-4 md:w-5 md:h-5 text-gray-500 dark:text-gray-400"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 dark:text-gray-400 mb-0.5 uppercase font-medium">Address</p>
          <p class="text-xs sm:text-sm text-gray-900 dark:text-white break-words">{{ user.address }}</p>
        </div>
      </div>
      
      <div v-if="user.relationship_status" class="flex items-start gap-2 sm:gap-2.5">
        <div class="mt-0.5">
          <i data-lucide="heart" class="w-3.5 h-3.5 sm:w-4 sm:h-4 md:w-5 md:h-5 text-gray-500 dark:text-gray-400"></i>
        </div>
        <div>
          <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 dark:text-gray-400 mb-0.5 uppercase font-medium">Relationship Status</p>
          <p class="text-xs sm:text-sm text-gray-900 dark:text-white capitalize">{{ user.relationship_status }}</p>
        </div>
      </div>
      
      <div v-if="user.contactLinksArray && user.contactLinksArray.length > 0" class="flex items-start gap-2 sm:gap-2.5">
        <div class="mt-0.5">
          <i data-lucide="link" class="w-3.5 h-3.5 sm:w-4 sm:h-4 md:w-5 md:h-5 text-gray-500 dark:text-gray-400"></i>
        </div>
        <div class="flex-1">
          <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 dark:text-gray-400 mb-1 sm:mb-1.5 uppercase font-medium">Contact Links</p>
          <div class="flex flex-wrap gap-1.5 sm:gap-2">
            <a 
              v-for="(link, index) in user.contactLinksArray" 
              :key="index"
              :href="link.url"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1 sm:gap-1.5 px-2 py-1 sm:px-3 sm:py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors text-[10px] sm:text-xs font-medium max-w-full"
            >
              <i data-lucide="external-link" class="w-3 h-3 sm:w-3.5 sm:h-3.5 flex-shrink-0"></i>
              <span class="truncate">{{ link.label }}</span>
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
        class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 sm:py-2.5 font-medium text-xs sm:text-sm transition-colors"
      >
        <i data-lucide="layout-grid" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
        <span>Posts</span>
      </button>
      <button 
        @click="activeTab = 'saved'" 
        :class="{'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white': activeTab === 'saved', 'text-gray-500 dark:text-gray-400': activeTab !== 'saved'}"
        class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 sm:py-2.5 font-medium text-xs sm:text-sm transition-colors"
      >
        <i data-lucide="bookmark" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
        <span>Saved</span>
      </button>
    </div>
  </div>
  
  <!-- Tab Content -->
  <div class="bg-white dark:bg-gray-800 rounded-b-xl sm:rounded-b-2xl w-full">
    <!-- Posts Tab -->
    <div v-if="activeTab === 'posts'" class="p-2 sm:p-3">
      <?= $this->element('posts/post_list', [
        'posts' => $postsArray ?? [],
        'currentUser' => $user ?? [],
        'emptyMessage' => 'No posts yet. Share your first post to get started!'
      ]) ?>
    </div>
    
    <!-- Saved Tab -->
    <div v-if="activeTab === 'saved'" class="p-2 sm:p-3">
      <div class="text-center py-8 sm:py-12">
        <i data-lucide="bookmark" class="w-10 h-10 sm:w-12 sm:h-12 text-gray-300 dark:text-gray-600 mx-auto mb-2 sm:mb-3"></i>
        <p class="text-gray-500 dark:text-gray-400 text-sm sm:text-base">No saved posts</p>
        <p class="text-gray-400 dark:text-gray-500 text-xs sm:text-sm mt-1">Save posts to view them later</p>
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
