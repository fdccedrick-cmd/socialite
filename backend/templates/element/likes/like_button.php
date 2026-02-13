<?php
/**
 * Like Button Element
 * Displays like and comment buttons with counts
 * 
 * @var array $post - Post data
 */
?>

<div class="p-3 sm:p-4 pt-2 sm:pt-3">
  <div class="flex items-center gap-4 sm:gap-5 mb-2 sm:mb-3">
    <button class="flex items-center gap-1 sm:gap-1.5 text-gray-600 hover:text-red-500 transition-colors">
      <i data-lucide="heart" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
      <span class="text-[10px] sm:text-xs font-medium">0</span>
    </button>
    <button class="flex items-center gap-1 sm:gap-1.5 text-gray-600 hover:text-blue-500 transition-colors">
      <i data-lucide="message-circle" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
      <span class="text-[10px] sm:text-xs font-medium">0</span>
    </button>
  </div>
</div>
