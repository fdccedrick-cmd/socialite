<?php
/**
 * @var \App\View\AppView $this
 * @var string $query
 * @var array $users
 * @var array $posts
 */
$this->assign('title', 'Search Results');
?>

<div id="searchApp" class="max-w-screen-2xl mx-auto px-3 sm:px-4 lg:px-8 py-4 sm:py-6 space-y-4" v-cloak>
  <!-- Search Header -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">
      Search Results
    </h1>
    <p class="text-sm sm:text-base text-gray-600">
      <?php if (!empty($query)): ?>
        Showing results for: <span class="font-semibold text-gray-900">"<?= h($query) ?>"</span>
      <?php else: ?>
        Enter a search query to find people and posts
      <?php endif; ?>
    </p>
  </div>

  <?php if (!empty($query)): ?>
    <!-- People Results -->
    <?php if (!empty($users)): ?>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100">
          <div class="flex items-center gap-2">
            <i data-lucide="users" class="w-5 h-5 text-indigo-600"></i>
            <h2 class="text-lg sm:text-xl font-bold text-gray-900">People</h2>
            <span class="text-sm text-gray-500">(<?= count($users) ?>)</span>
          </div>
        </div>
        
        <div class="divide-y divide-gray-100">
          <?php foreach ($users as $user): ?>
            <div class="p-4 sm:p-6 hover:bg-gray-50 transition-colors">
              <div class="flex items-center gap-3 sm:gap-4">
                <!-- Avatar -->
                <a href="/profile/<?= h($user['username']) ?>" class="shrink-0">
                  <img 
                    src="<?= h($user['profile_photo_path'] ?: 'https://i.pravatar.cc/150?img=1') ?>" 
                    alt="<?= h($user['full_name']) ?>"
                    class="w-12 h-12 sm:w-16 sm:h-16 rounded-full object-cover border-2 border-gray-100"
                  />
                </a>
                
                <!-- User Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <a href="/profile/<?= h($user['username']) ?>" class="font-semibold text-gray-900 hover:text-indigo-600 text-sm sm:text-base truncate">
                      <?= h($user['full_name']) ?>
                    </a>
                    <?php if ($user['is_friend']): ?>
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 shrink-0">
                        <i data-lucide="check" class="w-3 h-3"></i>
                        Friend
                      </span>
                    <?php endif; ?>
                  </div>
                  <p class="text-xs sm:text-sm text-gray-500">@<?= h($user['username']) ?></p>
                  <?php if (!empty($user['bio'])): ?>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1 line-clamp-1"><?= h($user['bio']) ?></p>
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
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100">
          <div class="flex items-center gap-2">
            <i data-lucide="file-text" class="w-5 h-5 text-indigo-600"></i>
            <h2 class="text-lg sm:text-xl font-bold text-gray-900">Posts</h2>
            <span class="text-sm text-gray-500">(<?= count($posts) ?>)</span>
          </div>
        </div>
        
        <div class="divide-y divide-gray-100">
          <?php foreach ($posts as $post): ?>
            <div class="p-4 sm:p-6 hover:bg-gray-50 transition-colors">
              <!-- Post Header -->
              <div class="flex items-start gap-3 mb-3">
                <a href="/profile/<?= h($post['user']['username']) ?>" class="shrink-0">
                  <img 
                    src="<?= h($post['user']['profile_photo_path'] ?: 'https://i.pravatar.cc/150?img=1') ?>" 
                    alt="<?= h($post['user']['full_name']) ?>"
                    class="w-10 h-10 rounded-full object-cover"
                  />
                </a>
                <div class="flex-1 min-w-0">
                  <a href="/profile/<?= h($post['user']['username']) ?>" class="font-semibold text-gray-900 hover:text-indigo-600 text-sm">
                    <?= h($post['user']['full_name']) ?>
                  </a>
                  <p class="text-xs text-gray-500">
                    <?php 
                    $created = new DateTime($post['created']);
                    echo $created->format('M j, Y \a\t g:i A');
                    ?>
                  </p>
                </div>
              </div>
              
              <!-- Post Content -->
              <a href="/posts/view/<?= $post['id'] ?>" class="block mb-3">
                <p class="text-sm sm:text-base text-gray-700 line-clamp-3"><?= h($post['content']) ?></p>
              </a>
              
              <!-- Post Images (if any) -->
              <?php if (!empty($post['post_images'])): ?>
                <a href="/posts/view/<?= $post['id'] ?>" class="block mb-3">
                  <div class="grid gap-2 <?= count($post['post_images']) > 1 ? 'grid-cols-2' : '' ?>">
                    <?php foreach (array_slice($post['post_images'], 0, 4) as $image): ?>
                      <img 
                        src="<?= h($image['image_path']) ?>" 
                        alt="Post image"
                        class="rounded-lg object-cover w-full <?= count($post['post_images']) === 1 ? 'max-h-96' : 'h-32 sm:h-48' ?>"
                      />
                    <?php endforeach; ?>
                  </div>
                </a>
              <?php endif; ?>
              
              <!-- Post Stats -->
              <div class="flex items-center gap-4 text-sm text-gray-500 pt-3 border-t">
                <span class="flex items-center gap-1">
                  <i data-lucide="heart" class="w-4 h-4"></i>
                  <?= $post['like_count'] ?>
                </span>
                <span class="flex items-center gap-1">
                  <i data-lucide="message-circle" class="w-4 h-4"></i>
                  <?= $post['comment_count'] ?>
                </span>
                <a href="/posts/view/<?= $post['id'] ?>" class="ml-auto text-indigo-600 hover:text-indigo-700 font-medium">
                  View Post
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- No Results -->
    <?php if (empty($users) && empty($posts)): ?>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
          <i data-lucide="search" class="w-8 h-8 text-gray-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">No results found</h3>
        <p class="text-gray-600">
          Try searching for something else or check your spelling
        </p>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <!-- Empty State -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-indigo-100 flex items-center justify-center">
        <i data-lucide="search" class="w-8 h-8 text-indigo-600"></i>
      </div>
      <h3 class="text-lg font-semibold text-gray-900 mb-2">Start searching</h3>
      <p class="text-gray-600">
        Use the search bar above to find people and posts
      </p>
    </div>
  <?php endif; ?>
</div>

<script>
(function() {
  const el = document.getElementById('searchApp');
  if (!el) return;
  
  const app = Vue.createApp({
    data() {
      return {};
    },
    mounted() {
      if (window.lucide) {
        lucide.createIcons();
      }
    }
  });
  
  app.mount('#searchApp');
})();
</script>
