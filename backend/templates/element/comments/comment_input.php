<?php
/**
 * Comment Input Element
 * Vue.js component for adding comments to a post
 * 
 * @var array $post - Post data
 */
?>

<!-- Comment Input -->
<div class="px-3 sm:px-4 pb-3 pt-2">
  <div class="flex items-center gap-1.5 sm:gap-2">
    <img 
      :src="user.avatar" 
      :alt="user.username" 
      class="w-6 h-6 sm:w-8 sm:h-8 rounded-full object-cover border border-gray-200 flex-shrink-0"
    />
    <div class="flex-1 relative">
      <input 
        v-model="post.newComment"
        @keyup.enter="submitComment(post.id)"
        @focus="showCommentOptions(post.id)"
        type="text" 
        placeholder="Write a comment..." 
        class="w-full px-2 sm:px-3 py-1 sm:py-1.5 bg-gray-50 rounded-full text-[10px] sm:text-xs text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
    </div>
    <button 
      @click="document.getElementById('comment-image-' + post.id).click()"
      class="p-1 sm:p-1.5 text-gray-600 hover:bg-gray-100 rounded-full transition-colors flex-shrink-0"
      title="Add image"
    >
      <i data-lucide="image" class="w-3 h-3 sm:w-3.5 sm:h-3.5"></i>
    </button>
    <input 
      :id="'comment-image-' + post.id"
      type="file"
      accept="image/*"
      @change="handleCommentImage($event, post.id)"
      class="hidden"
    />
    <button 
      @click="submitComment(post.id)"
      class="p-1 sm:p-1.5 text-blue-600 hover:bg-blue-50 rounded-full transition-colors flex-shrink-0"
    >
      <i data-lucide="send" class="w-3 h-3 sm:w-3.5 sm:h-3.5"></i>
    </button>
  </div>
  <!-- Image Preview -->
  <div v-if="post.commentImage" class="mt-2 relative inline-block">
    <img :src="post.commentImagePreview" alt="Preview" class="max-w-[150px] max-h-[150px] rounded border">
    <button 
      @click="removeCommentImage(post.id)"
      class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600"
    >
      ×
    </button>
  </div>
</div>
