<?php
/**
 * Post List Element
 * Displays a list of posts (empty state or post cards)
 * 
 * @var array $posts - Array of posts
 * @var array $currentUser - Current logged in user
 * @var string $emptyMessage - Optional custom empty message
 */
$posts = $posts ?? [];
$currentUser = $currentUser ?? [];
$emptyMessage = $emptyMessage ?? 'No posts yet. Be the first to share something!';
?>

<div class="space-y-3 sm:space-y-4">
  <!-- Empty state -->
  <div v-if="posts.length === 0" class="bg-white dark:bg-gray-800 rounded-lg sm:rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sm:p-8 text-center">
    <i data-lucide="inbox" class="w-10 h-10 sm:w-12 sm:h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2 sm:mb-3"></i>
    <p class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm"><?= h($emptyMessage) ?></p>
  </div>
  
  <!-- Dynamic Posts -->
  <div v-for="post in posts" :key="post.id">
    <?= $this->element('posts/post_card', ['post' => $post ?? [], 'currentUser' => $currentUser]) ?>
  </div>
</div>
