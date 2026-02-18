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
        bio: <?= json_encode(!empty($user['bio']) ? $user['bio'] : 'No bio yet') ?>
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
          <p class="text-gray-700 text-xs sm:text-sm lg:text-base">{{ user.bio }}</p>
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
      
      <!-- Action Buttons -->
      <div class="shrink-0 md:self-start w-full md:w-auto">
        <!-- Edit Profile Button (Own Profile) -->
        <button 
          v-if="isOwnProfile"
          @click="openEditModal" 
          class="flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-1.5 sm:py-2 pr-1.5 sm:pr-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors w-full md:w-auto"
        >
          <i data-lucide="settings" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-700"></i>
          <span class="text-xs sm:text-sm font-medium text-gray-700">Edit Profile</span>
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

          <!-- Request Sent (Pending - Current User Sent) -->
          <button 
            v-if="friendshipStatus === 'pending' && isSender"
            @click="cancelFriendRequest"
            class="flex items-center justify-center gap-1.5 sm:gap-2 px-4 sm:px-5 py-2 sm:py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors w-full md:w-auto font-medium text-xs sm:text-sm"
          >
            <i data-lucide="clock" class="w-4 h-4"></i>
            <span>Request Sent</span>
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
              class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10"
            >
              <button 
                @click.stop="unfriend"
                class="w-full flex items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 transition-colors"
              >
                <i data-lucide="user-minus" class="w-4 h-4"></i>
                <span>Unfriend</span>
              </button>
            </div>
          </div>

          <!-- Message Button (optional) -->
          <button 
            v-if="friendshipStatus === 'accepted'"
            class="flex items-center justify-center gap-1.5 sm:gap-2 px-4 sm:px-5 py-2 sm:py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg transition-colors font-medium text-xs sm:text-sm"
          >
            <i data-lucide="message-circle" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Message</span>
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
  <div class="bg-white rounded-b-xl sm:rounded-b-2xl w-full ">
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
              :src="postDetailView.post.post_images[postDetailView.imageIndex].image_path"
              :alt="'Image ' + (postDetailView.imageIndex + 1)"
              class="max-w-full max-h-full object-contain"
            />
            <button v-if="postDetailView.imageIndex > 0" @click="postDetailPrevImage" class="absolute left-2 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-2">
              <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
            <button v-if="postDetailView.imageIndex < postDetailView.post.post_images.length - 1" @click="postDetailNextImage" class="absolute right-2 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-2">
              <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>
            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 text-white text-xs bg-black bg-opacity-50 px-2 py-1 rounded-full">
              {{ postDetailView.imageIndex + 1 }} / {{ postDetailView.post.post_images.length }}
            </div>
          </template>
          <div v-else class="text-gray-400 text-sm">No image</div>
        </div>
        <div class="w-full sm:w-96 flex flex-col border-t sm:border-t-0 sm:border-l border-gray-200 bg-white overflow-hidden">
          <div class="p-3 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <button @click="closePostDetailView" class="p-1.5 hover:bg-gray-100 rounded-full" title="Close">
              <i data-lucide="x" class="w-5 h-5 text-gray-600"></i>
            </button>
            <span class="text-sm font-medium text-gray-700">Post</span>
            <div class="w-8"></div>
          </div>
          <div class="flex-1 overflow-y-auto p-3 space-y-3">
            <div class="flex gap-2">
              <img :src="postDetailView.post.user?.profile_photo_path || 'https://i.pravatar.cc/150?img=1'" :alt="postDetailView.post.user?.full_name" class="w-9 h-9 rounded-full object-cover flex-shrink-0" />
              <div class="min-w-0 flex-1">
                <a :href="`/profile/${postDetailView.post.user?.id}`" class="font-semibold text-sm text-gray-900 hover:underline">{{ postDetailView.post.user?.full_name }}</a>
                <p class="text-xs text-gray-500">{{ formatDate(postDetailView.post.created) }}</p>
                <p v-if="postDetailView.post.content_text" class="text-sm text-gray-800 whitespace-pre-wrap mt-1">{{ postDetailView.post.content_text }}</p>
              </div>
            </div>
            <template v-if="postDetailView.currentImageId">
              <div class="flex items-center gap-4 pt-2 border-t border-gray-100">
                <button @click.prevent="toggleImageLike()" :class="postDetailView.imageIsLiked ? 'text-red-500' : 'text-gray-700 hover:text-red-500'" class="flex items-center gap-1.5 transition-colors">
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
                  <input v-model="postDetailView.imageNewComment" @keyup.enter="submitImageComment()" type="text" placeholder="Write a comment..." class="flex-1 px-3 py-1.5 bg-gray-50 rounded-full text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                  <button @click="submitImageComment()" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-full">
                    <i data-lucide="send" class="w-4 h-4"></i>
                  </button>
                </div>
              </div>
            </template>
            <template v-else>
              <div class="flex items-center gap-4 pt-2 border-t border-gray-100">
                <button @click.prevent="toggleLike(postDetailView.post.id)" :class="postDetailView.post.is_liked ? 'text-red-500' : 'text-gray-700 hover:text-red-500'" class="flex items-center gap-1.5 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" :fill="postDetailView.post.is_liked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l8 8Z"></path>
                  </svg>
                  <span class="text-sm font-semibold">{{ postDetailView.post.like_count || 0 }}</span>
                </button>
                <button @click="handleOpenComment(postDetailView.post.id)" class="flex items-center gap-1.5 text-gray-700 hover:text-blue-500">
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

<script src="/js/profile.js?v=<?= time() ?>"></script>
