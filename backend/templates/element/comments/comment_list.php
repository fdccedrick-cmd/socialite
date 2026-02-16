<?php
/**
 * Comment List Element
 * Vue.js component for displaying comments
 * 
 * @var array $post - Post data with comments
 */
?>

<!-- Show Comments Button (if has comments) -->
<div v-if="post.comment_count > 0" class="px-3 sm:px-4 pt-2">
  <button 
    @click="toggleComments(post.id)"
    class="text-gray-600 hover:text-gray-900 text-[10px] sm:text-xs font-medium"
  >
    <span v-if="!post.showComments">View all {{ post.comment_count }} {{ post.comment_count === 1 ? 'comment' : 'comments' }}</span>
    <span v-else>Hide comments</span>
  </button>
</div>

<!-- Comments List (expandable) -->
<div v-if="post.showComments" class="px-3 sm:px-4 py-2 space-y-2 max-h-[400px] overflow-y-auto">
  <div v-for="comment in post.comments" :key="comment.id" class="flex gap-2 text-xs sm:text-sm">
    <img 
      :src="comment.user?.profile_photo_path || 'https://i.pravatar.cc/150?img=1'" 
      :alt="comment.user?.full_name" 
      class="w-6 h-6 sm:w-8 sm:h-8 rounded-full object-cover flex-shrink-0"
    />
    <div class="flex-1 min-w-0">
      <div class="bg-gray-100 rounded-2xl px-3 py-2 inline-block max-w-full">
        <!-- <p class="font-semibold text-[10px] sm:text-xs">{{ comment.user?.full_name }}</p> -->
         <a v-if="comment.user" 
            :href="`/profile/${comment.user.id}`"
            class="font-semibold text-[10px] sm:text-xs text-gray-900 hover:underline cursor-pointer inline-block"
         >
            {{ comment.user.full_name }}
         </a>
         <p v-else class="font-semibold text-[10px] sm:text-xs text-gray-900">{{ comment.user?.full_name || 'Unknown User' }}</p>
        <p v-if="comment.content_text" class="text-gray-800 text-[10px] sm:text-xs whitespace-pre-wrap break-words">{{ comment.content_text }}</p>
        <img v-if="comment.content_image_path" 
             :src="'/' + comment.content_image_path" 
             alt="Comment image"
             class="mt-1 rounded max-w-[200px] max-h-[200px]"
        />
      </div>
      <div class="flex items-center gap-3 mt-1 px-2">
        <button 
          @click="toggleCommentLike(post.id, comment.id)"
          class="text-[9px] sm:text-[10px] font-medium"
          :class="comment.is_liked ? 'text-red-600' : 'text-gray-500 hover:text-gray-700'"
        >
          {{ comment.is_liked ? 'Liked' : 'Like' }} 
          <span v-if="comment.like_count > 0">({{ comment.like_count }})</span>
        </button>
        <span class="text-[9px] sm:text-[10px] text-gray-500">{{ formatDate(comment.created_at) }}</span>
      </div>
    </div>
  </div>
</div>
