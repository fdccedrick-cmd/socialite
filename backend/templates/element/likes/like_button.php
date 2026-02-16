<?php
/**
 * Like Button Element
 * Displays like and comment buttons with counts
 * 
 * @var array $post - Post data
 */
?>

<div class="relative w-full p-3 sm:p-4 pt-2 sm:pt-3 bg-white">
  <div class="flex items-center gap-4 sm:gap-5 mb-2 sm:mb-3">
    <!-- Like Button -->
    <button 
      @click.prevent="toggleLike(post.id)"
      :class="post.is_liked ? 'text-red-500' : 'text-gray-700 hover:text-red-500'"
      class="flex items-center gap-1 sm:gap-1.5 transition-all duration-200 active:scale-110"
      :title="post.is_liked ? 'Unlike' : 'Like'"
    >
      <svg 
        :key="'heart-' + post.id + '-' + post.is_liked"
        xmlns="http://www.w3.org/2000/svg" 
        width="16" 
        height="16" 
        viewBox="0 0 24 24" 
        :fill="post.is_liked ? 'currentColor' : 'none'"
        stroke="currentColor" 
        stroke-width="2" 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        class="w-3.5 h-3.5 sm:w-4 sm:h-4 transition-transform"
      >
        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l8 8Z"></path>
      </svg>
      <span class="text-[10px] sm:text-xs font-semibold">{{ post.like_count || 0 }}</span>
    </button>
    
    <!-- Comment Button -->
    <button 
      @click.prevent="openCommentInput(post.id)"
      class="flex items-center gap-1 sm:gap-1.5 text-gray-700 hover:text-blue-500 transition-colors"
      title="Comment"
    >
      <i data-lucide="message-circle" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
      <span class="text-[10px] sm:text-xs font-semibold">{{ post.comment_count || 0 }}</span>
    </button>
  </div>
</div>

