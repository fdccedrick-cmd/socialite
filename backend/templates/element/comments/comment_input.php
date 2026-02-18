<?php
/**
 * Comment Input Element — Facebook-style
 * Avatar + "Write a comment..." + image + send
 *
 * @var array $post - Post data
 */
?>

<!-- Facebook-style comment input -->
<div class="px-3 sm:px-4 pb-3 pt-2 border-t border-gray-100">
  <div class="flex items-center gap-2">
    <img
      :src="user && user.avatar ? user.avatar : 'https://i.pravatar.cc/150?img=1'"
      alt=""
      class="w-8 h-8 rounded-full object-cover flex-shrink-0"
    />
    <div class="flex-1 flex items-center gap-1 bg-gray-100 rounded-full pl-3 pr-1 py-1">
      <input
        :id="'comment-input-' + post.id"
        v-model="post.newComment"
        @keyup.enter="submitComment(post.id)"
        @focus="showCommentOptions(post.id)"
        type="text"
        placeholder="Write a comment..."
        class="flex-1 min-w-0 bg-transparent text-sm text-gray-800 placeholder-gray-500 focus:outline-none py-1"
      />
      <button
        @click="triggerCommentImageInput(post.id)"
        type="button"
        class="p-1.5 text-gray-500 hover:bg-gray-200 rounded-full transition-colors"
        title="Add photo"
      >
        <i data-lucide="image" class="w-4 h-4"></i>
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
        type="button"
        class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors"
        :class="{ 'text-blue-600': (post.newComment && post.newComment.trim()) || post.commentImage }"
        title="Send"
      >
        <i data-lucide="send" class="w-4 h-4"></i>
      </button>
    </div>
  </div>
  <!-- Image preview (Facebook-style) -->
  <div v-if="post.commentImage" class="mt-2 ml-10 relative inline-block">
    <img :src="post.commentImagePreview" alt="Preview" class="max-w-[180px] max-h-[180px] rounded-lg border border-gray-200 object-cover">
    <button
      @click="removeCommentImage(post.id)"
      type="button"
      class="absolute -top-1 -right-1 bg-gray-800 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-gray-700"
    >
      ×
    </button>
  </div>
</div>
