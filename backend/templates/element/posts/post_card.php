<?php
/**
 * Post Card Element
 * Displays a single post with images, likes, and comments
 * 
 * @var array $post - Post data with user and images
 * @var array $currentUser - Current logged in user data
 */
$currentUser = $currentUser ?? [];
?>

<div class="bg-white dark:bg-gray-800 rounded-lg sm:rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
  <!-- Post Header -->
  <div class="p-3 sm:p-4 pb-2 sm:pb-3">
    <div class="flex items-center gap-2 sm:gap-2.5 mb-2 sm:mb-3">
      <img 
        :src="post.user.profile_photo_path || 'https://i.pravatar.cc/150?img=1'" 
        :alt="post.user.full_name" 
        class="w-8 h-8 sm:w-10 sm:h-10 rounded-full object-cover border border-gray-200 dark:border-gray-600 flex-shrink-0"
      />
      <div class="flex-1 min-w-0">
        <a :href="`/profile/${post.user.id}`"
           class="block font-semibold text-xs sm:text-sm text-gray-900 dark:text-white truncate hover:underline cursor-pointer inline-block">
            {{ post.user.full_name }}
        </a>
        <!-- <h3 class="font-semibold text-gray-900 dark:text-white text-xs sm:text-sm truncate">{{ post.user.full_name }}</h3> -->
        <div class="flex items-center gap-1 text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">
          <span>{{ formatDate(post.created) }}</span>
          <span>•</span>
          <i 
            :data-lucide="post.privacy === 'public' ? 'globe' : (post.privacy === 'friends' ? 'users' : 'lock')" 
            class="w-3 h-3"
            :title="post.privacy === 'public' ? 'Public' : (post.privacy === 'friends' ? 'Friends Only' : 'Private')"
          ></i>
        </div>
      </div>
      
      <!-- Post Options Menu (3 dots) - Only show for own posts -->
      <div v-if="typeof safeCanEditPost === 'function' && safeCanEditPost(post)" class="relative">
        <button 
          @click="togglePostMenu(post.id, $event)"
          data-menu-trigger
          class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
        >
          <i data-lucide="more-horizontal" class="w-4 h-4 text-gray-600 dark:text-gray-400"></i>
        </button>
        
        <div 
          v-if="post.showMenu"
          data-post-menu
          class="absolute right-0 mt-1 w-32 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 py-1 z-10"
        >
          <button
            @click="editPost(post.id)"
            class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
          >
            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
            Edit
          </button>
          <button
            @click="deletePost(post.id)"
            class="w-full px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 flex items-center gap-2"
          >
            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            Delete
          </button>
        </div>
      </div>
    </div>
    
    <!-- Edit Mode -->
    <div v-if="post.isEditing" class="space-y-3">
      <!-- Edit Caption -->
      <textarea 
        v-model="post.editContent"
        placeholder="What's on your mind?"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-xs sm:text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        rows="3"
      ></textarea>
      
      <!-- Edit Privacy -->
      <div class="flex items-center gap-2">
        <label class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">Privacy:</label>
        <select 
          v-model="post.editPrivacy"
          class="appearance-none pl-3 pr-8 py-1.5 text-xs sm:text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors cursor-pointer text-gray-900 dark:text-white"
        >
          <option value="public">🌍 Public</option>
          <option value="friends">👥 Friends</option>
          <option value="private">🔒 Private</option>
        </select>
      </div>
      
      <!-- Existing Images in Edit Mode -->
      <div v-if="post.editImages && post.editImages.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        <div v-for="(image, index) in post.editImages" :key="image.id" class="relative">
          <img :src="image.image_path" alt="Post image" class="w-full h-24 sm:h-32 object-cover rounded-lg">
          <button
            @click="removeExistingImage(post.id, image.id, index)"
            class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
          >
            ×
          </button>
        </div>
      </div>
      
      <!-- New Images Preview -->
      <div v-if="post.newEditImages && post.newEditImages.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        <div v-for="(preview, index) in post.newEditImagePreviews" :key="'new-' + index" class="relative">
          <img :src="preview" alt="New image" class="w-full h-24 sm:h-32 object-cover rounded-lg">
          <button
            @click="removeNewEditImage(post.id, index)"
            class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
          >
            ×
          </button>
        </div>
      </div>
      
      <!-- Add Photos Button -->
      <div class="flex gap-2">
        <input 
          :id="'edit-images-' + post.id"
          type="file"
          accept="image/*"
          multiple
          @change="handleEditImageSelect($event, post.id)"
          class="hidden"
        />
        <button
          @click="document.getElementById('edit-images-' + post.id).click()"
          class="flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
        >
          <i data-lucide="image" class="w-3.5 h-3.5"></i>
          Add Photos
        </button>
      </div>
      
      <!-- Save/Cancel Buttons -->
      <div class="flex gap-2 pt-2 border-t dark:border-gray-700">
        <button
          @click="saveEditPost(post.id)"
          class="flex-1 px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
        >
          Save
        </button>
        <button
          @click="cancelEditPost(post.id)"
          class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs sm:text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
        >
          Cancel
        </button>
      </div>
    </div>
    
    <!-- Normal View Mode -->
    <div v-else>
      <!-- Post Content (click opens Facebook-style detail view) -->
      <div 
        v-if="post.content_text" 
        class="text-gray-800 dark:text-gray-200 text-xs sm:text-sm whitespace-pre-wrap cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 -mx-1 px-1 rounded" 
        :class="{'mb-2 sm:mb-3': post.post_images && post.post_images.length > 0}"
        @click="safeOpenPostDetailView(post, 0)"
      >{{ post.content_text }}</div>
    </div>
  </div>
  
  <!-- Post Images (only show when not editing); click opens detail view with photo left, caption/comments/likes right -->
  <div v-if="!post.isEditing && post.post_images && post.post_images.length > 0" class="w-full bg-black">
    <!-- Single Image - Facebook style with smart aspect ratio -->
    <div 
      v-if="post.post_images.length === 1"
      class="w-full relative overflow-hidden cursor-pointer bg-black"
      style="max-height: 600px;"
      @click="safeOpenPostDetailView(post, 0)"
    >
      <img 
        :src="post.post_images[0].image_path" 
        :alt="'Post image'" 
        loading="lazy"
        class="w-full h-full object-contain hover:opacity-95 transition-all duration-200"
        style="max-height: 600px; min-height: 200px;"
      />
    </div>
    
    <!-- Two Images Grid - Equal split, minimal gap -->
    <div v-else-if="post.post_images.length === 2" class="grid grid-cols-2 gap-px bg-black">
      <div
        v-for="(image, index) in post.post_images" 
        :key="index"
        class="relative overflow-hidden cursor-pointer group"
        style="height: 350px;"
        @click="safeOpenPostDetailView(post, index)"
      >
        <img 
          :src="image.image_path" 
          :alt="'Post image ' + (index + 1)" 
          loading="lazy"
          class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        />
      </div>
    </div>
    
    <!-- Three Images Grid (Facebook Style: 1 large left, 2 stacked right) -->
    <div v-else-if="post.post_images.length === 3" class="grid grid-cols-2 gap-px bg-black" style="height: 400px;">
      <div class="relative overflow-hidden cursor-pointer group" @click="safeOpenPostDetailView(post, 0)">
        <img 
          :src="post.post_images[0].image_path" 
          alt="Post image 1" 
          loading="lazy"
          class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        />
      </div>
      <div class="grid grid-rows-2 gap-px">
        <div class="relative overflow-hidden cursor-pointer group" @click="safeOpenPostDetailView(post, 1)">
          <img 
            :src="post.post_images[1].image_path" 
            alt="Post image 2" 
            loading="lazy"
            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        </div>
        <div class="relative overflow-hidden cursor-pointer group" @click="safeOpenPostDetailView(post, 2)">
          <img 
            :src="post.post_images[2].image_path" 
            alt="Post image 3" 
            loading="lazy"
            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        </div>
      </div>
    </div>
    
    <!-- Four Images Grid - Perfect 2x2 -->
    <div v-else-if="post.post_images.length === 4" class="grid grid-cols-2 gap-px bg-black">
      <div 
        v-for="(image, index) in post.post_images" 
        :key="index"
        class="relative overflow-hidden cursor-pointer group"
        style="height: 250px;"
        @click="safeOpenPostDetailView(post, index)"
      >
        <img 
          :src="image.image_path" 
          :alt="'Post image ' + (index + 1)" 
          loading="lazy"
          class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        />
      </div>
    </div>
    
    <!-- Five or More Images Grid - Show first 4 in 2x2 grid with +X overlay on 4th -->
    <div v-else class="grid grid-cols-2 gap-px bg-black">
      <div 
        v-for="(image, index) in post.post_images.slice(0, 4)" 
        :key="index"
        class="relative overflow-hidden cursor-pointer group"
        style="height: 250px;"
        @click="safeOpenPostDetailView(post, index)"
      >
        <img 
          :src="image.image_path" 
          :alt="'Post image ' + (index + 1)" 
          loading="lazy"
          class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        />
        <!-- Overlay for remaining images on 4th image -->
        <div 
          v-if="index === 3 && post.post_images.length > 4"
          class="absolute inset-0 bg-black bg-opacity-70 flex items-center justify-center group-hover:bg-opacity-75 transition-all duration-200"
        >
          <span class="text-white text-4xl sm:text-5xl font-bold drop-shadow-lg">+{{ post.post_images.length - 4 }}</span>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Post Actions (Likes & Comments) - Hide when editing -->
  <div v-if="!post.isEditing" class="relative w-full">
    <?= $this->element('likes/like_button', ['post' => $post ?? []]) ?>
  
    <!-- Comment Section -->
    <div class="border-t border-gray-100 dark:border-gray-700">
      <?= $this->element('comments/comment_list', ['post' => $post ?? []]) ?>
      <?= $this->element('comments/comment_input', ['post' => $post ?? []]) ?>
    </div>
  </div>
</div>
