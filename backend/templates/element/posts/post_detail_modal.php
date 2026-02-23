<?php
/**
 * Post Detail View Modal Component
 * Reusable Facebook-style post detail modal with image viewer and comments
 * 
 * This is used in both Dashboard and Search pages
 */
?>

<!-- Post Detail View (Enhanced modern style with thumbnails) -->
<transition name="modal-fade">
  <div
    v-if="postDetailView?.isOpen && postDetailView?.post"
    class="fixed inset-0 z-[9998] flex items-center justify-center bg-black/95 backdrop-blur-sm"
    @click.self="closePostDetailView"
  >
    <div class="w-full h-full max-w-7xl max-h-[95vh] m-3 md:m-6 flex flex-col sm:flex-row bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-2xl animate-scale-in" @click.stop>
      <!-- Left: Photo Viewer -->
      <div class="flex-1 min-w-0 min-h-0 flex flex-col bg-gradient-to-br from-gray-900 to-black relative overflow-hidden">
        <!-- Close button (mobile) -->
        <button @click="closePostDetailView" class="absolute top-3 right-3 z-20 p-2 bg-black/60 hover:bg-black/80 backdrop-blur-sm rounded-full transition-all sm:hidden">
          <i data-lucide="x" class="w-5 h-5 text-white"></i>
        </button>
        
        <template v-if="postDetailView.post.post_images && postDetailView.post.post_images.length > 0">
          <!-- Main Image Display -->
          <div class="flex-1 flex items-center justify-center p-4 relative overflow-hidden min-h-0">
            <transition name="image-fade" mode="out-in">
              <img
                :key="'img-' + postDetailView.imageIndex"
                :src="postDetailView.post.post_images[postDetailView.imageIndex].image_path"
                :alt="'Image ' + (postDetailView.imageIndex + 1)"
                class="w-full h-full object-contain rounded-lg shadow-2xl"
                style="max-height: 100%; max-width: 100%;"
              />
            </transition>
            
            <!-- Navigation Arrows - Fixed positioning within viewport -->
            <button
              v-show="postDetailView.post.post_images.length > 1"
              @click.stop="postDetailPrevImage"
              class="fixed left-4 top-1/2 -translate-y-1/2 text-white bg-black/70 hover:bg-black/90 backdrop-blur-sm rounded-full p-3 transition-all transform hover:scale-110 hover:-translate-x-1 z-[9999] group shadow-xl"
              style="max-width: 48px;"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                <polyline points="15 18 9 12 15 6"></polyline>
              </svg>
            </button>
            <button
              v-show="postDetailView.post.post_images.length > 1"
              @click.stop="postDetailNextImage"
              class="fixed right-8 top-1/2 -translate-y-1/2 text-white bg-black/70 hover:bg-black/90 backdrop-blur-sm rounded-full p-3 transition-all transform hover:scale-110 hover:translate-x-1 z-[9999] group shadow-xl sm:right-[500px]"
              style="max-width: 48px;"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </button>
            
            <!-- Image Counter Badge -->
            <div class="absolute top-4 left-4 bg-black/70 backdrop-blur-md text-white text-sm font-medium px-3 py-1.5 rounded-full shadow-lg">
              <span class="text-blue-400">{{ postDetailView.imageIndex + 1 }}</span> / {{ postDetailView.post.post_images.length }}
            </div>
          </div>
          
          <!-- Thumbnail Strip -->
          <div v-if="postDetailView.post.post_images.length > 1" class="px-4 pb-4">
            <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-gray-800">
              <button
                v-for="(image, index) in postDetailView.post.post_images"
                :key="'thumb-' + index"
                @click="postDetailView.imageIndex = index"
                :class="[
                  'flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-all duration-200',
                  postDetailView.imageIndex === index 
                    ? 'border-blue-500 ring-2 ring-blue-500/50 scale-105 shadow-lg' 
                    : 'border-gray-700 hover:border-gray-500 opacity-70 hover:opacity-100'
                ]"
              >
                <img
                  :src="image.image_path"
                  :alt="'Thumbnail ' + (index + 1)"
                  class="w-full h-full object-cover"
                />
              </button>
            </div>
          </div>
        </template>
        <div v-else class="flex-1 flex items-center justify-center">
          <div class="text-center">
            <i data-lucide="image-off" class="w-16 h-16 text-gray-600 mx-auto mb-3"></i>
            <p class="text-gray-500 text-sm">No image available</p>
          </div>
        </div>
      </div>
      
      <!-- Right: Caption, user, likes, comments -->
      <div class="w-full sm:w-[420px] flex flex-col border-t sm:border-t-0 sm:border-l border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-gray-50 dark:from-gray-900 to-white dark:to-gray-800">
          <div class="flex items-center gap-2">
            <i data-lucide="image" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
            <span class="text-base font-semibold text-gray-800 dark:text-white">Post Details</span>
          </div>
          <button @click="closePostDetailView" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors hidden sm:block" title="Close">
            <i data-lucide="x" class="w-5 h-5 text-gray-600 dark:text-gray-400"></i>
          </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">
          <!-- User + caption -->
          <div class="flex gap-3 p-3 bg-gradient-to-r from-blue-50 dark:from-blue-900/30 to-purple-50 dark:to-purple-900/30 rounded-xl border border-blue-100 dark:border-blue-800 shadow-sm">
            <img
              :src="postDetailView.post.user?.avatar || postDetailView.post.user?.profile_photo_path || '/img/default/default_avatar.jpg'"
              :alt="postDetailView.post.user?.full_name"
              class="w-11 h-11 rounded-full object-cover flex-shrink-0 ring-2 ring-white dark:ring-gray-700 shadow-md"
            />
            <div class="min-w-0 flex-1">
              <a :href="`/profile/${postDetailView.post.user?.id}`" class="font-semibold text-base text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                {{ postDetailView.post.user?.full_name }}
              </a>
              <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-0.5">
                <i data-lucide="clock" class="w-3 h-3"></i>
                {{ formatDate(postDetailView.post.created) }}
              </p>
              <div v-if="postDetailView.post.content_text" class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap mt-2 leading-relaxed">
                <!-- Show truncated text if it's long and not expanded -->
                <template v-if="postDetailView.post.content_text.length > 300 && !postDetailView.isExpanded">
                  <span>{{ postDetailView.post.content_text.substring(0, 300) }}...</span>
                  <button 
                    @click.stop="postDetailView.isExpanded = true"
                    class="ml-1 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 font-medium transition-colors"
                  >
                    See More
                  </button>
                </template>
                <!-- Show full text if short or expanded -->
                <template v-else>
                  <span>{{ postDetailView.post.content_text }}</span>
                  <button 
                    v-if="postDetailView.post.content_text.length > 300"
                    @click.stop="postDetailView.isExpanded = false"
                    class="ml-1 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 font-medium transition-colors"
                  >
                    See Less
                  </button>
                </template>
              </div>
            </div>
          </div>
          
          <!-- Privacy Notice for Private Profile/Cover Photos -->
          <div v-if="isPrivateProfileCoverPhoto(postDetailView.post)" class="flex items-start gap-2 p-3 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-xs sm:text-sm text-gray-700 dark:text-gray-300">
            <i data-lucide="lock" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span>This {{ postDetailView.post.is_profile_photo ? 'profile photo' : 'cover photo' }} is private. Likes and comments are disabled.</span>
          </div>
          
          <!-- When viewing an image: show this image's likes & comments (separate from post) -->
          <template v-if="postDetailView.currentImageId">
            <!-- Only show like/comment if NOT a private profile/cover photo -->
            <div v-if="!isPrivateProfileCoverPhoto(postDetailView.post)" class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600">
              <button
                @click.prevent="toggleImageLike()"
                :class="postDetailView.imageIsLiked ? 'text-red-500 scale-110' : 'text-gray-600 dark:text-gray-400 hover:text-red-500'"
                class="flex items-center gap-2 transition-all duration-200 hover:scale-105"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" :fill="postDetailView.imageIsLiked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                  <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l8 8Z"></path>
                </svg>
                <span class="text-sm font-bold">{{ postDetailView.imageLikeCount }}</span>
              </button>
              <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                <i data-lucide="message-circle" class="w-5 h-5"></i>
                <span class="text-sm font-semibold">{{ postDetailView.imageComments.length }}</span>
                <span class="text-sm">{{ postDetailView.imageComments.length === 1 ? 'comment' : 'comments' }}</span>
              </div>
            </div>
            
            <!-- Only show comments section if NOT a private profile/cover photo -->
            <div v-if="!isPrivateProfileCoverPhoto(postDetailView.post)" class="border-t border-gray-200 dark:border-gray-700 pt-3">
              <div class="flex items-center gap-2 mb-3">
                <i data-lucide="messages-square" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Photo Comments</p>
              </div>
              <div class="space-y-3 max-h-[250px] overflow-y-auto mb-3 pr-2 custom-scrollbar">
                <div v-for="comment in postDetailView.imageComments" :key="comment.id" class="flex gap-2.5 p-2.5 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                  <img :src="comment.user?.avatar || comment.user?.profile_photo_path || '/img/default/default_avatar.jpg'" :alt="comment.user?.full_name" class="w-8 h-8 rounded-full object-cover flex-shrink-0 ring-2 ring-white dark:ring-gray-600 shadow-sm" />
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
                          @click="saveImageCommentEdit(comment.id)"
                          class="px-3 py-1 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 transition-colors"
                        >Save</button>
                        <button
                          @click="cancelImageCommentEdit(comment.id)"
                          class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                        >Cancel</button>
                      </div>
                    </div>
                    
                    <!-- Normal View Mode -->
                    <div v-else>
                      <a :href="`/profile/${comment.user?.id}`" class="font-semibold text-sm text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ comment.user?.full_name }}</a>
                      <p v-if="comment.content_text" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap mt-0.5">{{ comment.content_text }}</p>
                      
                      <!-- Action buttons -->
                      <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <button
                          @click="toggleImageCommentLike(comment.id)"
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
                        <template v-if="typeof currentUserId !== 'undefined' && currentUserId && comment.user && comment.user.id === currentUserId">
                          <span class="text-gray-400 dark:text-gray-500 select-none">·</span>
                          <button
                            @click="editImageComment(comment.id)"
                            class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 font-semibold"
                          >Edit</button>
                        </template>
                        <!-- Delete button: comment owner OR post owner -->
                        <template v-if="typeof currentUserId !== 'undefined' && currentUserId && postDetailView.post && ((comment.user && comment.user.id === currentUserId) || (postDetailView.post.user && postDetailView.post.user.id === currentUserId))">
                          <span class="text-gray-400 dark:text-gray-500 select-none">·</span>
                          <button
                            @click.prevent="deleteImageComment(comment.id)"
                            class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 font-semibold"
                          >Delete</button>
                        </template>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-2 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
                <img :src="(currentUser && currentUser.avatar) ? currentUser.avatar : (user && user.avatar ? user.avatar : 'https://i.pravatar.cc/150?img=1')" alt="" class="w-8 h-8 rounded-full object-cover flex-shrink-0 ring-2 ring-blue-100 dark:ring-blue-900" />
                <input
                  v-model="postDetailView.imageNewComment"
                  @keyup.enter="submitImageComment()"
                  type="text"
                  placeholder="Add a comment..."
                  class="flex-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 border border-transparent focus:border-blue-300 dark:border-gray-600 transition-all text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                />
                <button @click="submitImageComment()" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-full transition-colors">
                  <i data-lucide="send" class="w-5 h-5"></i>
                </button>
              </div>
            </div>
          </template>
          
          <!-- When no image or post-level: show post likes & comments -->
          <template v-else>
            <!-- Only show like/comment if NOT a private profile/cover photo -->
            <div v-if="!isPrivateProfileCoverPhoto(postDetailView.post)" class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600">
              <button
                @click.prevent="handlePostLike ? handlePostLike(postDetailView.post.id) : toggleLike(postDetailView.post.id)"
                :class="postDetailView.post.is_liked ? 'text-red-500 scale-110' : 'text-gray-600 dark:text-gray-400 hover:text-red-500'"
                class="flex items-center gap-2 transition-all duration-200 hover:scale-105"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" :fill="postDetailView.post.is_liked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                  <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l8 8Z"></path>
                </svg>
                <span class="text-sm font-bold">{{ postDetailView.post.like_count || 0 }}</span>
              </button>
              <button
                @click="handleOpenComment(postDetailView.post.id)"
                class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200 hover:scale-105"
              >
                <i data-lucide="message-circle" class="w-6 h-6"></i>
                <span class="text-sm font-bold">{{ postDetailView.post.comment_count || 0 }}</span>
              </button>
            </div>
            
            <!-- Only show comments section if NOT a private profile/cover photo -->
            <div v-if="!isPrivateProfileCoverPhoto(postDetailView.post)" class="border-t border-gray-200 dark:border-gray-700 pt-3" v-for="post in (postDetailView.post ? [postDetailView.post] : [])" :key="'modal-' + post.id">
              <?= $this->element('comments/comment_list', ['post' => []]) ?>
              <?= $this->element('comments/comment_input', ['post' => []]) ?>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</transition>