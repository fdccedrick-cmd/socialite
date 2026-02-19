<?php
/**
 * Comment List Element — Facebook-style
 * View all X comments / Hide comments, then list with Like · Reply · time · Delete
 *
 * @var array $post - Post data with comments
 */
?>

<!-- Facebook-style: "View all X comments" / "Hide comments" -->
<div v-if="post.comment_count > 0" class="px-3 sm:px-4 pt-1">
  <button
    @click="toggleComments(post.id)"
    type="button"
    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-xs font-medium focus:outline-none"
  >
    <span v-if="!post.showComments">View all {{ post.comment_count }} {{ post.comment_count === 1 ? 'comment' : 'comments' }}</span>
    <span v-else>Hide comments</span>
  </button>
</div>

<!-- Comments list (expandable, Facebook-style) -->
<div v-if="post.showComments" class="px-3 sm:px-4 py-1 space-y-1 max-h-[400px] overflow-y-auto">
  <div v-for="comment in post.comments" :key="comment.id" :id="'comment-' + comment.id" class="flex gap-2 sm:gap-2.5 text-xs sm:text-sm">
    <a :href="comment.user ? `/profile/${comment.user.id}` : '#'" class="flex-shrink-0">
      <img
        :src="comment.user?.profile_photo_path || '/img/default/default_avatar.jpg'"
        :alt="comment.user?.full_name"
        class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-600"
      />
    </a>
    <div class="flex-1 min-w-0">
      <!-- Edit Mode -->
      <div v-if="comment.isEditing" class="space-y-2">
        <textarea 
          v-model="comment.editContent"
          placeholder="Edit your comment..."
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-xs sm:text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          rows="2"
        ></textarea>
        <div class="flex gap-2">
          <button
            @click="saveCommentEdit(post.id, comment.id)"
            class="px-3 py-1 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 transition-colors"
          >Save</button>
          <button
            @click="cancelCommentEdit(post.id, comment.id)"
            class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
          >Cancel</button>
        </div>
      </div>
      
      <!-- Normal View Mode -->
      <div v-else>
        <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl px-3 py-2 inline-block max-w-full">
          <a v-if="comment.user"
             :href="`/profile/${comment.user.id}`"
             class="font-semibold text-gray-900 dark:text-white hover:underline"
          >{{ comment.user.full_name }}</a>
          <p v-else class="font-semibold text-gray-900 dark:text-white">{{ comment.user?.full_name || 'Unknown User' }}</p>
          <p v-if="comment.content_text" class="text-gray-900 dark:text-gray-200 whitespace-pre-wrap break-words">{{ comment.content_text }}</p>
          <img v-if="comment.content_image_path"
               :src="'/' + comment.content_image_path"
               alt="Comment"
               @click="(window.openImageViewer || (typeof openImageViewer === 'function' ? openImageViewer : function(){}))('/' + comment.content_image_path)"
               class="mt-1 rounded-lg max-w-[200px] max-h-[200px] cursor-pointer object-cover"
          />
        </div>
        <!-- Facebook-style: Like · Reply · time · Edit · Delete -->
        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
        <button
          @click="toggleCommentLike(post.id, comment.id)"
          type="button"
          class="text-xs font-semibold hover:underline"
          :class="comment.is_liked ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
        >
          {{ comment.is_liked ? 'Liked' : 'Like' }}
        </button>
        <span v-if="comment.like_count > 0" class="text-gray-500 dark:text-gray-400 text-xs">{{ comment.like_count }}</span>
        <span class="text-gray-400 dark:text-gray-500 select-none">·</span>
        <span class="text-gray-500 dark:text-gray-400 text-xs">{{ formatDate(comment.created_at) }}</span>
        <!-- Edit button: only comment owner -->
        <template v-if="typeof user !== 'undefined' && user && comment.user && comment.user.id === user.id">
          <span class="text-gray-400 dark:text-gray-500 select-none">·</span>
          <button
            @click="editComment(post.id, comment.id)"
            class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 font-semibold"
          >Edit</button>
        </template>
        <!-- Delete button: comment owner OR post owner -->
        <template v-if="typeof user !== 'undefined' && user && ((comment.user && comment.user.id === user.id) || (post.user && post.user.id === user.id))">
          <span class="text-gray-400 dark:text-gray-500 select-none">·</span>
          <button
            @click.prevent="deleteComment(post.id, comment.id)"
            class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 font-semibold"
          >Delete</button>
        </template>
        </div>
      </div>
    </div>
  </div>
</div>
