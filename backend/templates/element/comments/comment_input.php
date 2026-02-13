<?php
/**
 * Comment Input Element
 * Displays comment input form
 * 
 * @var array $post - Post data
 * @var array $currentUser - Current logged in user
 */
$currentUser = $currentUser ?? [];
$avatar = $currentUser['profile_photo_path'] ?? 'https://i.pravatar.cc/150?img=1';
$username = $currentUser['username'] ?? 'user';
?>

<!-- Comment Input -->
<div class="px-3 sm:px-4 pb-3 sm:pb-4">
  <div class="flex items-center gap-1.5 sm:gap-2 pt-2 sm:pt-3 border-t border-gray-100">
    <img 
      :src="user.avatar" 
      :alt="user.username" 
      class="w-6 h-6 sm:w-8 sm:h-8 rounded-full object-cover border border-gray-200 flex-shrink-0"
    />
    <input 
      type="text" 
      placeholder="Write a comment..." 
      class="flex-1 min-w-0 px-2 sm:px-3 py-1 sm:py-1.5 bg-gray-50 rounded-full text-[10px] sm:text-xs text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
    />
    <button class="p-1 sm:p-1.5 text-blue-600 hover:bg-blue-50 rounded-full transition-colors flex-shrink-0">
      <i data-lucide="send" class="w-3 h-3 sm:w-3.5 sm:h-3.5"></i>
    </button>
  </div>
</div>
