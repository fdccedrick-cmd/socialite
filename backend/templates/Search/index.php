<?php
/**
 * @var \App\View\AppView $this
 * @var string $query
 * @var array $users
 * @var array $posts
 * @var array $currentUser
 */
$this->assign('title', 'Search Results');
?>

<div id="searchApp" class="max-w-screen-2xl mx-auto px-3 sm:px-4 lg:px-8 py-4 sm:py-6 space-y-4" v-cloak>
  <!-- Search Header -->
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-2">
      Search Results
    </h1>
    <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
      <?php if (!empty($query)): ?>
        Showing results for: <span class="font-semibold text-gray-900 dark:text-white">"<?= h($query) ?>"</span>
      <?php else: ?>
        Enter a search query to find people and posts
      <?php endif; ?>
    </p>
  </div>

  <?php if (!empty($query)): ?>
    <!-- People Results -->
    <?php if (!empty($users)): ?>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-2">
            <i data-lucide="users" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">People</h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">(<?= count($users) ?>)</span>
          </div>
        </div>
        
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
          <?php foreach ($users as $user): ?>
            <div class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <div class="flex items-center gap-3 sm:gap-4">
                <!-- Avatar -->
                <a href="/profile/<?= h($user['username']) ?>" class="shrink-0">
                  <img 
                    src="<?= h($user['profile_photo_path'] ?: 'https://i.pravatar.cc/150?img=1') ?>" 
                    alt="<?= h($user['full_name']) ?>"
                    class="w-12 h-12 sm:w-16 sm:h-16 rounded-full object-cover border-2 border-gray-100 dark:border-gray-600"
                  />
                </a>
                
                <!-- User Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <a href="/profile/<?= h($user['username']) ?>" class="font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 text-sm sm:text-base truncate">
                      <?= h($user['full_name']) ?>
                    </a>
                    <?php if ($user['is_friend']): ?>
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 shrink-0">
                        <i data-lucide="check" class="w-3 h-3"></i>
                        Friend
                      </span>
                    <?php endif; ?>
                  </div>
                  <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">@<?= h($user['username']) ?></p>
                  <?php if (!empty($user['bio'])): ?>
                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-1"><?= h($user['bio']) ?></p>
                  <?php endif; ?>
                </div>
                
                <!-- View Profile Button -->
                <a 
                  href="/profile/<?= h($user['username']) ?>"
                  class="shrink-0 px-3 sm:px-4 py-1.5 sm:py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs sm:text-sm font-medium transition-colors"
                >
                  View
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Posts Results -->
    <?php if (!empty($posts)): ?>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-2">
            <i data-lucide="file-text" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
            <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Posts</h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">(<?= count($posts) ?>)</span>
          </div>
        </div>
        
        <div class="divide-y divide-gray-100 p-4 sm:p-6 space-y-4">
          <!-- Use reusable post components -->
          <?= $this->element('posts/post_list', [
            'posts' => $posts ?? [],
            'currentUser' => isset($currentUser) && is_object($currentUser) ? $currentUser->toArray() : ($currentUser ?? []),
            'emptyMessage' => 'No posts match your search'
          ]) ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- No Results -->
    <?php if (empty($users) && empty($posts)): ?>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
          <i data-lucide="search" class="w-8 h-8 text-gray-400 dark:text-gray-500"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No results found</h3>
        <p class="text-gray-600 dark:text-gray-400">
          Try searching for something else or check your spelling
        </p>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center">
        <i data-lucide="search" class="w-8 h-8 text-indigo-600 dark:text-indigo-400"></i>
      </div>
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Start searching</h3>
      <p class="text-gray-600 dark:text-gray-400">
        Use the search bar above to find people and posts
      </p>
    </div>
  <?php endif; ?>

  <!-- Image Viewer Modal (reused from dashboard pattern) -->
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

  <!-- Post Detail View Modal (reused from dashboard pattern) -->
  <?= $this->element('posts/post_detail_modal') ?>

</div>

<!-- Pass data to search.js -->
<script>
window.searchData = {
  user: {
    id: <?= json_encode(isset($currentUser) && is_object($currentUser) ? $currentUser->id : ($currentUser['id'] ?? null)) ?>,
    username: <?= json_encode(isset($currentUser) && is_object($currentUser) ? $currentUser->username : ($currentUser['username'] ?? 'user')) ?>,
    avatar: <?= json_encode(isset($currentUser) && is_object($currentUser) ? $currentUser->profile_photo_path : ($currentUser['profile_photo_path'] ?? 'https://i.pravatar.cc/150?img=1')) ?>
  },
  posts: <?= json_encode($posts ?? []) ?>
};
</script>

<!-- Load scripts -->
<script src="/js/websocket-manager.js?v=<?= time() ?>"></script>
<script src="/js/search.js?v=<?= time() ?>"></script>
